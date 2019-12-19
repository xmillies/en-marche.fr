<?php

namespace AppBundle\Command;

use AppBundle\History\EmailSubscriptionHistoryHandler;
use AppBundle\Mailchimp\SignUp\SignUpHandler;
use AppBundle\Membership\UserEvent;
use AppBundle\Membership\UserEvents;
use AppBundle\Repository\AdherentRepository;
use League\Csv\Reader;
use League\Flysystem\Filesystem;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class MailchimpSignUpEmailsCommand extends Command
{
    protected static $defaultName = 'mailchimp:signup:emails';

    private $adherentRepository;
    private $historyManager;
    private $signUpHandler;
    private $storage;
    private $em;
    private $dispatcher;
    /** @var SymfonyStyle */
    private $io;

    public function __construct(
        AdherentRepository $adherentRepository,
        SignUpHandler $signUpHandler,
        EmailSubscriptionHistoryHandler $historyManager,
        Filesystem $storage,
        EventDispatcherInterface $dispatcher
    ) {
        $this->adherentRepository = $adherentRepository;
        $this->signUpHandler = $signUpHandler;
        $this->historyManager = $historyManager;
        $this->storage = $storage;
        $this->dispatcher = $dispatcher;

        parent::__construct();
    }

    protected function configure()
    {
        $this
            ->setDescription('Send SignUp form for each contact from CSV file')
            ->addArgument('file', InputArgument::REQUIRED, 'CSV file with emails on first column')
        ;
    }

    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        $this->io = new SymfonyStyle($input, $output);
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $file = $input->getArgument('file');

        if (!$this->storage->has($file)) {
            throw new \RuntimeException('File does not exist');
        }

        $csv = Reader::createFromStream($this->storage->readStream($file));
        $csv->setHeaderOffset(0);
        $total = $csv->count();

        if (!$this->io->confirm(sprintf('Are you sure to unsubscribe all notifications of %d emails?', $total))) {
            return 1;
        }

        $this->io->progressStart($total);

        foreach ($csv as $row) {
            if (!$adherent = $this->adherentRepository->findOneByEmail($email = $row['email'])) {
                continue;
            }

            $oldEmailsSubscriptions = $adherent->getSubscriptionTypes();

            // remove all subscriptions here
            foreach ($oldEmailsSubscriptions as $emailsSubscription) {
                $adherent->removeSubscriptionType($emailsSubscription);
            }

            $this->historyManager->handleSubscriptionsUpdate($adherent, $oldEmailsSubscriptions);
            $this->dispatcher->dispatch(UserEvents::USER_UPDATE_SUBSCRIPTIONS, new UserEvent($adherent, null, null, $oldEmailsSubscriptions));

            $this->io->progressAdvance();
        }

        $this->io->progressFinish();
    }
}
