<?php

/**
 * @author Stanislav Vetlovskiy
 * @date 16.07.2015
 */

namespace Erliz\SkyforgeBundle\Command;


use RuntimeException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class NormalizeCookieCommand extends Command
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('cookie:normalize')
            ->setDefinition($this->createDefinition())
            ->setDescription('Normalize cookie json from other mappings')
            ->setHelp(<<<EOF
The <info>%command.name%</info> find all new pantheons from communities list
EOF
            )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $filePath = $input->getArgument('file');
        if (!is_readable($filePath)) {
            throw new RuntimeException('Unable to read file');
        }

        $data = file_get_contents($filePath);
        $normalized = array();
        foreach (json_decode($data) as $cookie) {
            $normalized[] = array(
                "Name" => $cookie->name,
                "Value" => $cookie->value,
                "Domain" => $cookie->domain,
                "Path" => $cookie->path,
                "Max-Age" => null,
                "Expires" => (isset($cookie->expirationDate) ? (int)$cookie->expirationDate : time()) + 60000,
                "Secure" => $cookie->secure,
                "Discard" => false, //$cookie->hostOnly,
                "HttpOnly" => $cookie->httpOnly,
            );
        }

        file_put_contents($input->getArgument('output'), json_encode($normalized));
    }

    private function createDefinition()
    {
        return array(
            new InputArgument('file', InputArgument::REQUIRED, 'source file'),
            new InputArgument('output', InputArgument::REQUIRED, 'output file')
        );
    }
}
