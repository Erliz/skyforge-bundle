<?php

/**
 * @author Stanislav Vetlovskiy
 * @date 22.11.2014
 */

namespace Erliz\SkyforgeBundle\Command;


use Doctrine\ORM\EntityManager;
use Erliz\SilexCommonBundle\Command\ApplicationAwareCommand;
use Erliz\SkyforgeBundle\Entity\Player;
use Erliz\SkyforgeBundle\Entity\PlayerDateStat;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class MigrationCommand extends ApplicationAwareCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('orm:fixtures:migrate')
            ->setDescription('Dump all data from Data Base to fixtures file')
            ->setHelp(<<<EOF
The <info>%command.name%</info> load all data from Data Base to fixtures file
EOF
            )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $app = $this->getProjectApplication();
        /** @var EntityManager $em */
        $em = $app['orm.em'];

        $playersRepo = $em->getRepository('Erliz\SkyforgeBundle\Entity\Player');

        /** @var Player $player */
        foreach ($playersRepo->findAll() as $player) {
            $minPrestigeValue = null;
            /** @var PlayerDateStat $dateStat */
            foreach (array_reverse($player->getDateStat()->toArray()) as $dateStat) {
                if (is_null($minPrestigeValue) && $dateStat->getCurrentPrestige() > 0) {
                    $minPrestigeValue = $dateStat->getCurrentPrestige();
                }
                if ($minPrestigeValue > $dateStat->getCurrentPrestige()) {
                    $minPrestigeValue = $dateStat->getCurrentPrestige();
                }
            }
            foreach (array_reverse($player->getDateStat()->toArray()) as $dateStat) {
                if ($dateStat->getCurrentPrestige() > $minPrestigeValue) {
                    $dateStat->setCurrentPrestige($minPrestigeValue);
                    $dateStat->setMaxPrestige($minPrestigeValue);
                } else {
                    break;
                }
            }
        }

        $em->flush();

    }
}
