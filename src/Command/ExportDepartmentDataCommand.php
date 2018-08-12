<?php

namespace AppBundle\Command;

use AppBundle\Entity\Adherent;
use AppBundle\Entity\Committee;
use AppBundle\Entity\Donation;
use AppBundle\Entity\Event;
use AppBundle\Entity\EventRegistration;
use AppBundle\Entity\Transaction;
use AppBundle\Entity\Unregistration;
use Doctrine\ORM\EntityManagerInterface;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ExportDepartmentDataCommand extends Command
{
    protected static $defaultName = 'app:export:department-data';

    private $sheetTitles = [
        '75001',
        '75002',
        '75003',
        '75004',
        '75005',
        '75006',
        '75007',
        '75008',
        '75009',
        '75010',
        '75011',
        '75012',
        '75013',
        '75014',
        '75015',
        '75016',
        '75017',
        '75018',
        '75019',
        '75020',
    ];

    private $rowTitles = [
        'Adherent',
        'Nouveaux adherents',
        'Desadhésions',
        'Ratio ad/pop %',
        'Comités (sans historique)',
        'Comités en attente (sans historique)',
        'Adhérents membres de comités',
        'Ratio membre de comite par nbr adherents',
        'Evenements (sans historique)',
        'Inscrits à des evenements',
        'Adherents inscrits à des evenements',
        'Non-adherents inscrits à des evenements',
        '',
        'Inscriptions e-mails',
        'Inscrits à la liste globale',
        'Inscrits à la lettre du vendredi',
        'Adhérents inscrits à la liste globale',
        'Adhérents inscrits à la lettre du vendredi',
        'Adhérents inscrits aux mails de leur référent',
        '',
        'Dons',
        'Dons ponctuels',
        'Dons ponctuels par des adherents',
        'Montant dons ponctuels',
        'Dons mensuels',
        'Dons mensuels par des adhérents',
        'Montant dons mensuels',

    ];

    private $columnTitles = [
        '2017-01',
        '2017-02',
        '2017-03',
        '2017-04',
        '2017-05',
        '2017-06',
        '2017-07',
        '2017-08',
        '2017-09',
        '2017-10',
        '2017-11',
        '2017-12',
        '2018-01',
        '2018-02',
        '2018-03',
        '2018-04',
        '2018-05',
        '2018-06',
        '2018-07',
        '2018-08',
    ];

    private $em;

    public function __construct(EntityManagerInterface $em)
    {
        parent::__construct();

        $this->em = $em;
    }

    protected function configure()
    {
        $this->setDescription('Export department datas to spreadsheet');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $spreadsheet = new Spreadsheet();
        $spreadsheet->removeSheetByIndex(0);

        foreach ($this->sheetTitles as $zipCode) {
            $worksheet = new Worksheet($spreadsheet, $zipCode);
            $spreadsheet->addSheet($worksheet);

            $row = 2;
            foreach ($this->rowTitles as $rowTitle) {
                $worksheet->getCell("A$row")->setValue($rowTitle);
                $worksheet->getColumnDimension('A')->setAutoSize(true);

                $row++;
            }

            $column = 'B';
            foreach ($this->columnTitles as $columnTitle) {
                $date = \DateTime::createFromFormat('Y-m-d H:i:s', "$columnTitle-01 00:00:00");

                $worksheet
                    ->getCell($column.'1')
                    ->setValue(Date::PHPToExcel($date));
                $worksheet
                    ->getStyle($column.'1')
                    ->getNumberFormat()
                    ->setFormatCode(NumberFormat::FORMAT_DATE_XLSX17);

                // Nb adhérents
                $worksheet
                    ->getCell($column.'2')
                    ->setValue($this->countAdherents($zipCode, $date))
                ;
                // Nb Nouveaux adhérents
                $worksheet
                    ->getCell($column.'3')
                    ->setValue($this->countNewMemberships($zipCode, $date))
                ;
                // Nb Désadhésions
                $worksheet
                    ->getCell($column.'4')
                    ->setValue($this->countUnregistrations($zipCode, $date))
                ;

                // Nb Nouveaux comités approuvés aujourd'hui
                $worksheet
                    ->getCell($column.'6')
                    ->setValue($this->countNewCommittees($zipCode, $date, Committee::APPROVED))
                ;

                // Nb Nouveaux comités en attente aujourd'hui
                $worksheet
                    ->getCell($column.'7')
                    ->setValue($this->countNewCommittees($zipCode, $date, Committee::PENDING))
                ;

                // Nb Nouveaux événements SCHEDULED aujourd'hui
                $worksheet
                    ->getCell($column.'10')
                    ->setValue($this->countNewEvents($zipCode, $date, Event::STATUS_SCHEDULED))
                ;

                // Nb d'inscrits à des événements
                $worksheet
                    ->getCell($column.'11')
                    ->setValue($this->countEventRegistrations($zipCode, $date))
                ;

                // Nb de dons ponctuels
                $worksheet
                    ->getCell($column.'23')
                    ->setValue($this->countDonations($zipCode, $date, 'ponctual'))
                ;

                // Nb de dons mensuels
                $worksheet
                    ->getCell($column.'26')
                    ->setValue($this->countDonations($zipCode, $date, 'subscription'))
                ;

                $column++;
            }

            // Nb d'inscrits à la newsletter globale
            $worksheet
                ->getCell('U16')
                ->setValue($this->countAdherentsSubscribedToEmailNotification($zipCode, 'subscribed_emails_main', false))
            ;

            // Nb d'inscrits à la newsletter du vendredi
            $worksheet
                ->getCell('U17')
                ->setValue($this->countAdherentsSubscribedToEmailNotification($zipCode, 'subscribed_emails_weekly_letter', false))
            ;

            // Nb d'adhérents inscrits à la newsletter globale
            $worksheet
                ->getCell('U18')
                ->setValue($this->countAdherentsSubscribedToEmailNotification($zipCode, 'subscribed_emails_main', true))
            ;

            // Nb d'adhérents inscrits à la newsletter du vendredi
            $worksheet
                ->getCell('U19')
                ->setValue($this->countAdherentsSubscribedToEmailNotification($zipCode, 'subscribed_emails_weekly_letter', true))
            ;

            // Nb d'adhérents inscrits aux mails référent
            $worksheet
                ->getCell('U20')
                ->setValue($this->countAdherentsSubscribedToEmailNotification($zipCode, 'subscribed_emails_referents', true))
            ;
        }

        $spreadsheet->setActiveSheetIndex(0);

        $writer = IOFactory::createWriter($spreadsheet, 'Xlsx');
        $writer->save("data-paris.xlsx");

        $output->writeln('Done!');
    }

    private function countDonations(string $zipCode, \DateTime $month, string $type = 'ponctual'): int
    {
        $qb = $this
            ->em
            ->getRepository(Transaction::class)
            ->createQueryBuilder('t')
            ->select('COUNT(d)')
            ->innerJoin('t.donation', 'd')
            ->andWhere('t.payboxResultCode = :payboxSuccessCode')
            ->andWhere('d.createdAt BETWEEN :month AND :nextMonth')
            ->andWhere('d.postAddress.country = :country')
            ->andWhere('d.postAddress.postalCode = :postalCode')
            ->setParameter('payboxSuccessCode', Transaction::PAYBOX_SUCCESS)
            ->setParameter('month', $month)
            ->setParameter('nextMonth', $this->getNextMonthDate($month)->format('Y-m-d'))
            ->setParameter('country', 'FR')
            ->setParameter('postalCode', $zipCode)
        ;

        switch ($type) {
            case 'subscription':
                $qb
                    ->andWhere('d.duration != :duration')
                    ->setParameter('duration', 0)
                ;

                break;
            case 'ponctual':
                $qb
                    ->andWhere('d.duration = :duration')
                    ->setParameter('duration', 0)
                ;

                break;
        }

        return $qb->getQuery()->getSingleScalarResult();
    }

    private function countDonationsAmount(string $zipCode, \DateTime $month, string $type = 'ponctual'): int
    {
        $qb = $this
            ->em
            ->getRepository(Transaction::class)
            ->createQueryBuilder('t')
            ->select('SUM(d.amount)')
            ->innerJoin('t.donation', 'd')
            ->andWhere('t.payboxResultCode = :payboxSuccessCode')
            ->andWhere('d.createdAt BETWEEN :month AND :nextMonth')
            ->andWhere('d.postAddress.country = :country')
            ->andWhere('d.postAddress.postalCode = :postalCode')
            ->setParameter('payboxSuccessCode', Transaction::PAYBOX_SUCCESS)
            ->setParameter('month', $month)
            ->setParameter('nextMonth', $this->getNextMonthDate($month)->format('Y-m-d'))
            ->setParameter('country', 'FR')
            ->setParameter('postalCode', $zipCode)
        ;

        switch ($type) {
            case 'subscription':
                $qb
                    ->andWhere('d.duration != :duration')
                    ->setParameter('duration', 0)
                ;

                break;
            case 'ponctual':
                $qb
                    ->andWhere('d.duration = :duration')
                    ->setParameter('duration', 0)
                ;

                break;
        }

        return $qb->getQuery()->getSingleScalarResult();
    }

    private function countAdherents(string $zipCode, \DateTime $month): int
    {
        return $this
            ->em
            ->getRepository(Adherent::class)
            ->createQueryBuilder('a')
            ->select('COUNT(a)')
            ->andWhere('a.registeredAt < :nextMonth')
            ->andWhere('a.postAddress.country = :country')
            ->andWhere('a.postAddress.postalCode = :postalCode')
            ->andWhere('a.adherent = :adherent')
            ->setParameter('nextMonth', $this->getNextMonthDate($month)->format('Y-m-d'))
            ->setParameter('country', 'FR')
            ->setParameter('postalCode', $zipCode)
            ->setParameter('adherent', true)
            ->getQuery()
            ->getSingleScalarResult()
        ;
    }

    private function countNewMemberships(string $zipCode, \DateTime $month): int
    {
        return $this
            ->em
            ->getRepository(Adherent::class)
            ->createQueryBuilder('a')
            ->select('COUNT(a)')
            ->andWhere('a.registeredAt BETWEEN :month AND :nextMonth')
            ->andWhere('a.postAddress.country = :country')
            ->andWhere('a.postAddress.postalCode = :postalCode')
            ->andWhere('a.adherent = :adherent')
            ->setParameter('month', $month->format('Y-m-d'))
            ->setParameter('nextMonth', $this->getNextMonthDate($month)->format('Y-m-d'))
            ->setParameter('country', 'FR')
            ->setParameter('postalCode', $zipCode)
            ->setParameter('adherent', true)
            ->getQuery()
            ->getSingleScalarResult()
        ;
    }

    private function countUnregistrations(string $zipCode, \DateTime $month): int
    {
        return $this
            ->em
            ->getRepository(Unregistration::class)
            ->createQueryBuilder('u')
            ->select('COUNT(u)')
            ->andWhere('u.unregisteredAt BETWEEN :month AND :nextMonth')
            ->andWhere('u.postalCode = :postalCode')
            ->setParameter('month', $month->format('Y-m-d'))
            ->setParameter('nextMonth', $this->getNextMonthDate($month)->format('Y-m-d'))
            ->setParameter('postalCode', $zipCode)
            ->getQuery()
            ->getSingleScalarResult()
        ;
    }

    private function countNewCommittees(string $zipCode, \DateTime $month, string $actualStatus): int
    {
        return $this
            ->em
            ->getRepository(Committee::class)
            ->createQueryBuilder('c')
            ->select('COUNT(c)')
            ->andWhere('c.createdAt < :nextMonth')
            ->andWhere('c.postAddress.country = :country')
            ->andWhere('c.postAddress.postalCode = :postalCode')
            ->andWhere('c.status = :status')
            ->setParameter('nextMonth', $this->getNextMonthDate($month)->format('Y-m-d'))
            ->setParameter('country', 'FR')
            ->setParameter('postalCode', $zipCode)
            ->setParameter('status', $actualStatus)
            ->getQuery()
            ->getSingleScalarResult()
        ;
    }

    private function countNewEvents(string $zipCode, \DateTime $month, string $actualStatus): int
    {
        return $this
            ->em
            ->getRepository(Event::class)
            ->createQueryBuilder('e')
            ->select('COUNT(e)')
            ->andWhere('(e.beginAt BETWEEN :month AND :nextMonth OR e.finishAt BETWEEN :month AND :nextMonth)')
            ->andWhere('e.postAddress.country = :country')
            ->andWhere('e.postAddress.postalCode = :postalCode')
            ->andWhere('e.status = :status')
            ->setParameter('month', $month)
            ->setParameter('nextMonth', $this->getNextMonthDate($month)->format('Y-m-d'))
            ->setParameter('country', 'FR')
            ->setParameter('postalCode', $zipCode)
            ->setParameter('status', $actualStatus)
            ->getQuery()
            ->getSingleScalarResult()
        ;
    }

    private function countAdherentsSubscribedToEmailNotification(string $zipCode, string $notification, bool $filterAdherents = false): int
    {
        $qb = $this
            ->em
            ->getRepository(Adherent::class)
            ->createQueryBuilder('a')
            ->select('COUNT(a)')
            ->andWhere('a.postAddress.country = :country')
            ->andWhere('a.postAddress.postalCode = :postalCode')
            ->andWhere('FIND_IN_SET(:notification, a.emailsSubscriptions) > 0')
            ->setParameter('country', 'FR')
            ->setParameter('postalCode', $zipCode)
            ->setParameter('notification', $notification)
        ;

        if (true === $filterAdherents) {
            $qb
                ->andWhere('a.adherent = :adherent')
                ->setParameter('adherent', true)
            ;
        }

        return $qb
            ->getQuery()
            ->getSingleScalarResult()
        ;
    }

    private function countEventRegistrations(string $zipCode, \DateTime $month): int
    {
        return $this
            ->em
            ->getRepository(EventRegistration::class)
            ->createQueryBuilder('er')
            ->select('COUNT(er)')
            ->andWhere('er.createdAt BETWEEN :month AND :nextMonth')
            ->andWhere('er.postalCode = :postalCode')
            ->setParameter('month', $month)
            ->setParameter('nextMonth', $this->getNextMonthDate($month)->format('Y-m-d'))
            ->setParameter('postalCode', $zipCode)
            ->getQuery()
            ->getSingleScalarResult()
        ;
    }

    private function getNextMonthDate(\DateTime $month): \DateTime
    {
        $nextMonth = clone $month;

        return $nextMonth->add(new \DateInterval('P1M'));
    }
}
