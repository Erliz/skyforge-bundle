<?php
/**
 * @author Stanislav Vetlovskiy
 * @date   27.04.2015
 */

namespace Erliz\SkyforgeBundle\Service;


use Erliz\SilexCommonBundle\Service\ApplicationAwareService;
use Erliz\SkyforgeBundle\Entity\Player;

class BillingService extends ApplicationAwareService
{
    public function sendMessage($playerId, $message)
    {
        $app = $this->getApp();
        /** @var ParseService $parser */
        $parser = $app['parse.skyforge.service'];

        $parser->setAuthData($app['config']['skyforge']['statistic']);
        $response = $parser->getPage('https://portal.sf.mail.ru/wall/72298387084666795?page=1');
        $parser->sendMessage($playerId, $message);
    }

    public function getNotEnoughMoney(Player $player)
    {
        return sprintf(
            'Уважаемый %s, на вашем счёте задолжность в %s кредитов',
            $player->getName() ? $player->getName() : $player->getNick(),
            $player->getBalance() * -1
        );
    }
}