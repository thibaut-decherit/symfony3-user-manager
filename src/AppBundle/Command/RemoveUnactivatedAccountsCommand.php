<?php

namespace AppBundle\Command;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class SendAutoEmailCommand
 * @package AppBundle\Command
 */
class RemoveUnactivatedAccountsCommand extends Command
{
    /**
     * @var EntityManagerInterface
     */
    private $em;

    public function __construct(EntityManagerInterface $em)
    {
        parent::__construct();
        $this->em = $em;
    }

    protected function configure()
    {
        $this
            ->setName('app:remove-unactivated-accounts-older-than')
            ->setDescription('Removes unactivated accounts older than n days.')
            ->addArgument('days', InputArgument::REQUIRED);
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $limit = 100;
        $hasResults = true;
        $days = $input->getArgument('days');

        while ($hasResults) {
            $users = $this->em->getRepository('AppBundle:User')->findUnactivatedAccountsOlderThan($days, $limit);

            foreach ($users as $user) {
                $this->em->remove($user);
                $this->em->flush();
            }

            if (empty($users)) {
                $hasResults = false;
            }
        }
    }
}
