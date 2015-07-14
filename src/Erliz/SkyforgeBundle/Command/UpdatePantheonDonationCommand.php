<?php

/**
 * @author Stanislav Vetlovskiy
 * @date 22.11.2014
 */

namespace Erliz\SkyforgeBundle\Command;


use DateTime;
use Doctrine\ORM\EntityManager;
use Erliz\SilexCommonBundle\Command\ApplicationAwareCommand;
use Erliz\SkyforgeBundle\Entity\BillingTransaction;
use Erliz\SkyforgeBundle\Entity\Pantheon;
use Erliz\SkyforgeBundle\Entity\Player;
use Erliz\SkyforgeBundle\Repository\PlayerRepository;
use Erliz\SkyforgeBundle\Service\ParseService;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class UpdatePantheonDonationCommand extends ApplicationAwareCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('skyforge:pantheon:donat')
            ->setDefinition($this->createDefinition())
            ->setDescription('Update pantheon donations')
            ->setHelp(<<<EOF
The <info>%command.name%</info> load donations by players from pantheon community page
EOF
            )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $app = $this->getProjectApplication();
        /** @var EntityManager $em */
        $em = $app['orm.em'];
        /** @var ParseService $parseService */
        $parseService = $app['parse.skyforge.service'];
        $parseService->setAuthData($app['config']['skyforge']['pantheon']);

        /** @var PlayerRepository $playerRepository */
        $playerRepository = $em->getRepository('Erliz\SkyforgeBundle\Entity\Player');
        $pantheonRepository = $em->getRepository('Erliz\SkyforgeBundle\Entity\Pantheon');

        $lockFilePath = '/home/sites/erliz.ru/app/cache/curl/parse.lock';

        if (is_file($lockFilePath)) {
            throw new \RuntimeException('Another parse in progress');
        } else {
            file_put_contents($lockFilePath, getmypid());
        }

        /** @var Pantheon $pantheon */
        $pantheon = $pantheonRepository->find($input->getArgument('id'));

        $today = new \DateTime();
        $todayString = $today->format('d-m-Y');

        if ($input->getOption('update-donations')) {
            $playersDonationInfo = $parseService->getDonationFromPantheonPage($pantheon->getId());

            foreach ($playersDonationInfo as $playerId => $data)
            {
                $player = $playerRepository->find($playerId);
                if (!$player) {
                    $player = new Player();
                    $player
                        ->setId($playerId)
                        ->setNick($data['nick'])
                        ->setName($data['name'])
                        ->setPantheon($pantheon);
                    $em->persist($player);
                }
                if ($data['balance'] == 0) {
                    continue;
                }
                if ($input->getOption('first-update')) {
                    $transaction = new BillingTransaction();
                    $transaction
                        ->setPlayer($player)
                        ->setAction(BillingTransaction::DEPOSIT_ACTION)
                        ->setAmount($data['balance'])
                        ->setComment('First deposit');
                    $em->persist($transaction);

                    $wipeTransaction = new BillingTransaction();
                    $wipeTransaction
                        ->setPlayer($player)
                        ->setAction(BillingTransaction::TAX_ACTION)
                        ->setAmount($data['balance'])
                        ->setComment('Wipe charity donations');
                    $em->persist($wipeTransaction);
                } else {
                    $transactionsDepositAmount = 0;
                    $transactionsWithdrawAmount = 0;
                    $playerTransactions = $player->getBillingTransactions();
                    if (count($playerTransactions) > 0) {
                        $deposits = $playerTransactions->filter(
                            function ($el){
                                return $el->getAction() == BillingTransaction::DEPOSIT_ACTION;
                            }
                        );
                        $withdraws = $playerTransactions->filter(
                            function ($el){
                                return $el->getAction() == BillingTransaction::WITHDRAW_ACTION;
                            }
                        );

                        /** @var BillingTransaction $deposit */
                        foreach ($deposits as $deposit){
                            $transactionsDepositAmount += $deposit->getAmount();
                        }
                        /** @var BillingTransaction $withdraw */
                        foreach ($withdraws as $withdraw){
                            $transactionsWithdrawAmount -= $withdraw->getAmount();
                        }
                    }

                    $depositAmount = $data['balance'] - $transactionsDepositAmount + $transactionsWithdrawAmount;

                    if ($depositAmount == 0) {
                        continue;
                    }
                    if ($depositAmount < 0) {
                        $maxPlayerBalance = $this->getMaxAccountBalance($playerTransactions);
                        $leavePantheonTransaction = new BillingTransaction();
                        $leavePantheonTransaction
                            ->setPlayer($player)
                            ->setAction(BillingTransaction::WITHDRAW_ACTION)
                            ->setAmount($maxPlayerBalance)
                            ->setComment(sprintf('Return amount that was before pantheon leave'));
                        $em->persist($leavePantheonTransaction);

                        $depositAmount = $data['balance'] - $transactionsDepositAmount + $maxPlayerBalance;

                        if ($depositAmount < 0) {
                            $output->writeln(sprintf('<comment>Bad balance data on player %s "%s"</comment>', $playerId, $data['balance']));
                            continue;
                        }
                    }

                    $depositTransaction = new BillingTransaction();
                    $depositTransaction
                        ->setPlayer($player)
                        ->setAction(BillingTransaction::DEPOSIT_ACTION)
                        ->setAmount($depositAmount)
                        ->setComment(sprintf('Deposit %s total sum %s', $todayString, $data['balance']));
                    $em->persist($depositTransaction);
                }
            }
        }

        if ($input->getOption('add-tax')) {
            $taxAmount = 82500;
            foreach ($pantheon->getMembers() as $player) {
                $transaction = new BillingTransaction();
                $transaction
                    ->setPlayer($player)
                    ->setAction(BillingTransaction::TAX_ACTION)
                    ->setAmount($taxAmount)
                    ->setComment('Tax pay for ' . $todayString);
                $em->persist($transaction);
            }
        }

        $em->flush();


        unlink($lockFilePath);
    }

    /**
     * @param BillingTransaction[] $playerTransactions
     *
     * @return int
     */
    private function getMaxAccountBalance($playerTransactions)
    {
        $maxBalance = 0;
        foreach ($playerTransactions as $transaction) {
            if ($transaction->getAction() == BillingTransaction::DEPOSIT_ACTION) {
                $balance = $this->getAccountBalanceFromComment($transaction->getComment());
                $maxBalance = $balance > $maxBalance ? $balance : $maxBalance;
            }
        }

        return $maxBalance;
    }

    private function createDefinition()
    {
        return array(
            new InputOption('add-tax', 't', InputOption::VALUE_NONE, 'add tax for all pantheon players'),
            new InputOption('update-donations', 'u', InputOption::VALUE_NONE, 'Update player donations from pantheon page'),
            new InputOption('first-update', 'f', InputOption::VALUE_NONE, 'Flag to remove all charity donations'),
            new InputArgument('id', InputArgument::REQUIRED, 'id of community to parse')
        );
    }

    /**
     * @param string $comment
     *
     * @return int
     */
    private function getAccountBalanceFromComment($comment)
    {
        preg_match('/ (\d+)$/', $comment, $matches);
        if (!empty($matches)) {
            return (int) $matches[1];
        }

        return 0;
    }
}
