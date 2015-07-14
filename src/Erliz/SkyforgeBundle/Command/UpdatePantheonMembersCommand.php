<?php

/**
 * @author Stanislav Vetlovskiy
 * @date 22.11.2014
 */

namespace Erliz\SkyforgeBundle\Command;


use Erliz\SilexCommonBundle\Command\ApplicationAwareCommand;
use Monolog\Logger;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
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

        $ids = $input->getArgument('ids');
        if (!$ids) {
            throw new \InvalidArgumentException('No pantheons ids to parse');
        }

        $pantheonInfoCommand = new UpdatePantheonInfoCommand();
        $pantheonInfoCommand->setProjectApplication($app);

        $playersStatCommand = new UpdatePlayerStatCommand();
        $playersStatCommand->setProjectApplication($app);

        foreach ($ids as $id) {
            $pantheonInfoCommand->run(new ArrayInput(array('--id' => $id)), $output);
            $playersStatCommand->run(new ArrayInput(array('--id' => $id)), $output);
        }
    }

    private function createDefinition()
    {
        return array(
            new InputArgument('ids', InputArgument::IS_ARRAY, 'Pantheons ids'),
        );
    }
}

