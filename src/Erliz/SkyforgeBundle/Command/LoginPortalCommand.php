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
use JonnyW\PhantomJs\Client;
use Monolog\Logger;
use RuntimeException;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class LoginPortalCommand extends ApplicationAwareCommand
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
            ->setName('skyforge:portal:login')
            ->setDefinition($this->createDefinition())
            ->setDescription('Login in to portal by credentials')
            ->setHelp(<<<EOF
The <info>%command.name%</info> login in to portal by credentials and save the cookie jar file
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

        $authData = $this->regionService->getAuthOptions();

        $client = Client::getInstance();

//        $client->getEngine()->debug(true);
        $client->getEngine()->addOption('--config=' . $app['config']['app']['path'] . '/phantomjs.json');
        $client->getEngine()->setPath($app['config']['app']['path'] . '/../bin/phantomjs');

//        $request = $client->getMessageFactory()->createCaptureRequest($authData['url'], $authData['method']);
//        $request->setRequestData($authData['data']);
        $request = $client->getMessageFactory()->createCaptureRequest('http://erliz.ru/', 'GET');
        $response = $client->getMessageFactory()->createResponse();

        $request->setDelay(5);
        $request->setViewportSize(1920, 1080);
        $request->setOutputFile($app['config']['app']['path'].'/log/screen_test_'.time().'.jpg');

        $client->send($request, $response);

        if($response->getStatus() === 200) {
            print_r($response->getContent());
        }

//        print_r($client->getLog());
    }

    private function createDefinition()
    {
        return array(
            new InputOption('region', 'r', InputOption::VALUE_REQUIRED, 'region of skyforge project'),
        );
    }
}

