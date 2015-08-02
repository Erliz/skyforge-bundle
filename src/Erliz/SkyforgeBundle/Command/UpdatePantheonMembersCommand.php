<?php

/**
 * @author Stanislav Vetlovskiy
 * @date 22.11.2014
 */

namespace Erliz\SkyforgeBundle\Command;


use Doctrine\ORM\EntityManager;
use Erliz\SilexCommonBundle\Command\ApplicationAwareCommand;
use Erliz\SkyforgeBundle\Service\RegionService;
use Monolog\Logger;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class UpdatePantheonMembersCommand extends ApplicationAwareCommand
{
    /** @var Logger */
    private $logger;

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('skyforge:pantheon:members')
            ->setDefinition($this->createDefinition())
            ->setDescription('Update pantheon members progress')
            ->setHelp(<<<EOF
The <info>%command.name%</info> load all data from pantheon community page and update players with it
EOF
            )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $app = $this->getProjectApplication();
        $this->logger = $this->getLogger();

        /** @var RegionService regionService */
        $regionService = $app['region.skyforge.service'];
        $regionService->setRegion($input->getOption('region'));

        /** @var EntityManager $em */
        $em = $app['orm.ems'][$regionService->getDbConnectionNameByRegion()];

        $pantheonInfoCommand = new UpdatePantheonInfoCommand();
        $pantheonInfoCommand->setProjectApplication($app);

        $playersStatCommand = new UpdatePlayerStatCommand();
        $playersStatCommand->setProjectApplication($app);

        $pantheonDateStatCommand = new UpdatePantheonDateStatCommand();
        $pantheonDateStatCommand->setProjectApplication($app);

        if ($input->getOption('ids')) {
            $communityIds = $input->getOption('ids');
        } else {
            $sqlResponse = $em->createQuery(
                "SELECT pt.id, count(pl.id) cnt
                    FROM Erliz\SkyforgeBundle\Entity\Pantheon pt
                    JOIN pt.members pl
                    group by pt.id
                    order by cnt DESC"
            )->getScalarResult();

            $communityIds = array_map('current', $sqlResponse);
        }

        $communitiesCount = count($communityIds);

        $lastId = $input->getOption('lastId');
        foreach ($communityIds as $index => $communityId) {
            if ($communityId == $lastId){
                $lastId = false;
            }
            if ($lastId) {
                continue;
            }
            $arguments = new ArrayInput(array('--id' => $communityId, '-r' => $input->getOption('region')));

            $pantheonInfoCommand->run($arguments, $output);
            $playersStatCommand->run($arguments, $output);
            $this->logger->addInfo(sprintf('Processed %s / %s', $index + 1, $communitiesCount));
            $pantheonDateStatCommand->run($arguments, $output);
        }
    }

    private function createDefinition()
    {
        return array(
            new InputOption('region', 'r', InputOption::VALUE_REQUIRED, 'region of skyforge project'),
            new InputOption('lastId', 'l', InputOption::VALUE_REQUIRED, 'id of last parsed community'),
            new InputOption('ids', 'i', InputOption::VALUE_OPTIONAL, 'Pantheons ids'),
        );
    }
}

