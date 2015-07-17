<?php

/**
 * @author Stanislav Vetlovskiy
 * @date 16.07.2015
 */

namespace Erliz\SkyforgeBundle\Command;


use DateTime;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Erliz\SilexCommonBundle\Command\ApplicationAwareCommand;
use Erliz\SkyforgeBundle\Entity\Pantheon;
use Erliz\SkyforgeBundle\Service\ParseService;
use Erliz\SkyforgeBundle\Service\RegionService;
use Monolog\Logger;
use RuntimeException;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class FindPantheonInfoCommand extends ApplicationAwareCommand
{
    /** @var ParseService */
    private $parseService;
    /** @var RegionService */
    private $regionService;
    /** @var EntityManager */
    private $em;
    /** @var EntityRepository */
    private $pantheonRepository;
    /** @var Logger */
    private $logger;

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('skyforge:pantheon:find')
            ->setDefinition($this->createDefinition())
            ->setDescription('Find new pantheons')
            ->setHelp(<<<EOF
The <info>%command.name%</info> find all new pantheons from communities list
EOF
            )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $app = $this->getProjectApplication();
        $this->logger = $this->getLogger();

        $this->regionService = $app['region.skyforge.service'];
        $this->regionService->setRegion($input->getOption('region'));

        $this->em = $app['orm.ems'][$this->regionService->getDbConnectionNameByRegion()];

        $this->parseService = $app['parse.skyforge.service'];
        $this->parseService->setAuthData($this->regionService->getCredentials());

        $this->pantheonRepository = $this->em->getRepository('Erliz\SkyforgeBundle\Entity\Pantheon');

        $lockFilePath = $app['config']['app']['path'].'/cache/curl/parse.lock';

        if (is_file($lockFilePath)) {
            throw new \RuntimeException('Another parse in progress');
        } else {
            file_put_contents($lockFilePath, getmypid());
        }

        $this->findCommunities($output);

        unlink($lockFilePath);
    }

    private function makeCommunitiesUrl()
    {
        return $this->regionService->getProjectUrl() . 'communities/';
    }

    private function makeCommunitiesMoreUrl()
    {
        return $this->regionService->getProjectUrl() . 'communities.morebutton:loadbunch';
    }

    /**
     * @param OutputInterface $output
     */
    private function findCommunities(OutputInterface $output)
    {
        $this->logger->addInfo('Finding new communities');
        $communities = array();
        try {
            for($page = 1; $page <= 20; $page++) {
                $responseMessage = $this->parseService->getPage(
                    $this->makeCommunitiesMoreUrl(),
                    true,
                    $this->makeCommunitiesUrl(),
                    array('t:zone' => 'bunchZone', 'bunchIndex' => $page)
                );

                $response = json_decode($responseMessage);
                if (!$response) {
                    $this->logger->addInfo(sprintf('Empty page %s', $page));
                    break;
                }
                $pageCommunities = $this->parseService->getCommunities($response->content);

                $this->logger->addInfo(sprintf('Page %s parsed successful, get %s communities', $page, count($pageCommunities)));
                $communities = $communities + $pageCommunities;
                usleep(rand(500, 1500) * 1000);
            }
        } catch (RuntimeException $e) {
            $this->logger->addInfo('Exception: ' . $e->getMessage() . ' ' . $e->getCode());
        }

        foreach ($communities as $parsedCommunity) {
            $community = $this->pantheonRepository->find($parsedCommunity->id);
            if ($community) {
                continue;
            }

            $community = new Pantheon();
            $community
                ->setId($parsedCommunity->id)
                ->setImg($parsedCommunity->pic)
                ->setName($parsedCommunity->name)
                ->setIsActive(0)
                ->setUpdatedAt(new DateTime());

            $this->em->persist($community);
        }

        $this->em->flush();
    }

    private function createDefinition()
    {
        return array(
            new InputOption('region', 'r', InputOption::VALUE_REQUIRED, 'region of skyforge project'),
        );
    }
}

