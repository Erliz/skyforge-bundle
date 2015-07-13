<?php

/**
 * @author Stanislav Vetlovskiy
 * @date 22.11.2014
 */

namespace Erliz\SkyforgeBundle\Command;



use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Query;
use Erliz\SilexCommonBundle\Command\ApplicationAwareCommand;
use Erliz\SkyforgeBundle\Entity\Item\ItemCollection;
use Erliz\SkyforgeBundle\Entity\Item\ItemRawData;
use Erliz\SkyforgeBundle\Service\ParseService;
use Erliz\SkyforgeBundle\Service\StatService;
use RuntimeException;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class ParsePlayerItemsCommand extends ApplicationAwareCommand
{
    /** @var ParseService */
    private $parseService;
    /** @var ItemCollection */
    private $itemCollection;
    /** @var EntityManager */
    private $em;

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('skyforge:items:parse')
            ->setDefinition($this->createDefinition())
            ->setDescription('Get from web all items from players')
            ->setHelp(<<<EOF
The <info>%command.name%</info> parse items in web on avatar dress page
EOF
            )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $app = $this->getProjectApplication();
        /** @var EntityManager $em */
        $this->em = $app['orm.em'];
        /** @var StatService $statService */
        $this->parseService = $app['parse.skyforge.service'];
        $this->parseService->setAuthData($app['config']['skyforge']['statistic']);
        $this->itemCollection = new ItemCollection($this->em->getRepository('Erliz\SkyforgeBundle\Entity\Item\Item')->findAll());
        $lockFilePath = '/home/sites/erliz.ru/app/cache/curl/parse.lock';

        if (is_file($lockFilePath) && !$input->getOption('force')) {
            throw new RuntimeException('Another parse in progress');
        } else {
            file_put_contents($lockFilePath, getmypid());
        }

        if ($input->getOption('id')) {
            $this->getPlayerItems($input->getOption('id'), $output);
        } else {
            $qb = $this->em->createQueryBuilder();
            $playerQuery = $qb
                ->select('Player.id')
                ->from('Erliz\SkyforgeBundle\Entity\Player', 'Player');
            $playersId = $playerQuery->getQuery()->getResult(Query::HYDRATE_SCALAR);

            $lastParsedIdFile = '/home/sites/erliz.ru/app/cache/curl/last_parse_id.log';
            $parsedToId = trim(file_get_contents($lastParsedIdFile));

            $continueParse = $parsedToId ? false : true;

            foreach ($playersId as $player) {
                if(!$continueParse) {
                    if ($player['id'] == $parsedToId) {
                        $continueParse = true;
                    } else {
                        continue;
                    }
                }
                try {
                    $this->getPlayerItems($player['id'], $output);
                    file_put_contents($lastParsedIdFile, $player['id']);
                    sleep(rand(3,5));
                } catch (\InvalidArgumentException $e) {
                    throw new RuntimeException(sprintf(
                        'On page: https://portal.sf.mail.ru/user/avatar/%s',
                        $player['id']
                    ), null, $e);
                }
            }
        }

        unlink($lockFilePath);
    }

    /**
     * @param string $playerId
     * @param OutputInterface $output
     */
    private function getPlayerItems($playerId, OutputInterface $output)
    {
        $data = $this->parseService->getPlayerItems($playerId);
        if (empty($data->resp)) {
            $output->writeln(sprintf('<comment>Empty dress response for %s</comment>', $playerId));
            return;
        }
        $slots = $data->resp->slot2item;

        if (isset($slots->MAINHAND)) {
            if ($slots->MAINHAND->name == 'Оберег') {
                $slots->MAINHAND->name = 'Метла';
            }
            $this->addItem($slots->MAINHAND, $playerId, $output);
        }
        if (isset($slots->OFFHAND)) {
            $this->addItem($slots->OFFHAND, $playerId, $output);
        }
        if (isset($slots->RING_LUCK)) {
            $this->addItem($slots->RING_LUCK, $playerId, $output);
        }
        if (isset($slots->RING_PRECISION)) {
            $this->addItem($slots->RING_PRECISION, $playerId, $output);
        }
        if (isset($slots->RING_VALOR)) {
            $this->addItem($slots->RING_VALOR, $playerId, $output);
        }
        if (isset($slots->RING_ZEAL)) {
            $this->addItem($slots->RING_ZEAL, $playerId, $output);
        }
        if (isset($slots->RUNE_1)) {
            $this->addItem($slots->RUNE_1, $playerId, $output);
        }
        if (isset($slots->RUNE_2)) {
            $this->addItem($slots->RUNE_2, $playerId, $output);
        }
        if (isset($slots->RUNE_3)) {
            $this->addItem($slots->RUNE_3, $playerId, $output);
        }
        if (isset($slots->RUNE_4)) {
            $this->addItem($slots->RUNE_4, $playerId, $output);
        }
/*        if ($slots->TROPHY_1) {
            $this->addItem($slots->MAINHAND, $playerId, $output);
        }
        if ($slots->TROPHY_2) {
            $this->addItem($slots->MAINHAND, $playerId, $output);
        }*/

        $this->em->flush();

        $output->writeln(sprintf('<info>Successfully parsed player %s</info>', $playerId));
    }

    private function addItem($slot, $playerId, OutputInterface $output)
    {
        /** @var ItemRawData $item */
        $item = $this->createItem($slot);
        $item->setPlayer($playerId);
        $id = $item->getGeneratedId();
        $itemName = $item->getItem()->getName();
        if($this->em->find('Erliz\SkyforgeBundle\Entity\Item\ItemRawData', $id)) {
            $output->writeln(sprintf("Duplicate entry with name '%s' ", $itemName, $id));
            return;
        }
        $output->writeln(sprintf("<info>Add item with name '%s'</info>", $itemName));
        $this->em->persist($item);
    }

    /**
     * @param \stdClass $slot
     *
     * @return ItemRawData
     */
    private function createItem($slot)
    {
        $item = $this->itemCollection->findOneByName($slot->name);
        if (!$item) {
            throw new \InvalidArgumentException(sprintf("No item with name '%s' in data base", $slot->name));
        }
        
        $data = new ItemRawData();
        $data
            ->setItem($item)
            ->setPrestige($slot->prestige)
            ->setLevel($slot->level)
            ->setQuality($slot->quality)
            ->setResourceId($slot->resourceId);
        
        if (!empty($slot->innateStats)) {
            $props = array();
            foreach ($slot->innateStats as $el) {
                $props[$el->properties->name] = $el->properties->value;
            }
            foreach ($slot->acquiredStats as $el) {
                $props[$el->properties->name] = $el->properties->value;
            }
            $propString = serialize($props);
            if (strlen($propString) > 255) {
                throw new RuntimeException(sprintf("Properties string '%s' is longer then 255 chars", $propString));
            }
            $data->setPropertiesString($propString);
        }

        if (!empty($slot->talents)) {
            $talents = array();
            foreach ($slot->talents as $talent) {
                $talents[] = $talent;
            }
            $talentsString = serialize($talents);
            if (strlen($talentsString) > 1024) {
                throw new RuntimeException(sprintf("Talents string '%s' is longer then 1024 chars", $talentsString));
            }

            $data->setTalentsString($talentsString);
        }

        return $data;
    }

    /**
     * @return array
     */
    private function createDefinition()
    {
        return array(
            new InputOption('id', 'i', InputOption::VALUE_REQUIRED, 'id of user to parse'),
            new InputOption('force', 'f', InputOption::VALUE_NONE, 'ignore lock file')
        );
    }
}
