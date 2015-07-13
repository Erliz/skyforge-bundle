<?php
/**
 * @author Stanislav Vetlovskiy
 * @date   20.01.2015
 */

namespace Erliz\SkyforgeBundle\Repository;

// "ARCHITECT" => Архитектор
// "ATHLETE" => Целитель
// "SCOUT" => Мистик
// "PHILOSOPHER" => Заклинатель

class FollowerRepository extends SimpleAbstractRepository
{

    protected function getItemsName()
    {
        return 'follower';
    }
}