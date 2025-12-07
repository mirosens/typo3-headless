<?php

declare(strict_types=1);

namespace Vendor\FahnCore\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * A.7.5.1: CLI-Command für Accessibility-Audit
 * 
 * Prüft alle sys_file_reference-Einträge auf fehlende Alt-Texte
 * bei nicht-dekorativen Bildern. Bricht mit Fehlercode ab, wenn
 * Verstöße gefunden werden (für CI/CD-Integration).
 */
class AccessibilityAuditCommand extends Command
{
    protected ConnectionPool $connectionPool;

    public function __construct(ConnectionPool $connectionPool = null)
    {
        parent::__construct();
        $this->connectionPool = $connectionPool ?? GeneralUtility::makeInstance(ConnectionPool::class);
    }

    protected function configure(): void
    {
        $this->setDescription('Prüft alle Bildreferenzen auf WCAG 2.2-konforme Alt-Texte');
        $this->setHelp('Dieser Command prüft, ob alle nicht-dekorativen Bilder einen Alt-Text haben.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('Accessibility-Audit: Alt-Text-Prüfung');

        $queryBuilder = $this->connectionPool->getQueryBuilderForTable('sys_file_reference');

        // A.7.5.1: Suche nach Bildern ohne Alt-Text, die nicht dekorativ sind
        $rows = $queryBuilder
            ->select('uid', 'pid', 'uid_local', 'tablenames', 'fieldname', 'alternative', 'tx_is_decorative')
            ->from('sys_file_reference')
            ->where(
                $queryBuilder->expr()->eq('tx_is_decorative', 0),
                $queryBuilder->expr()->or(
                    $queryBuilder->expr()->isNull('alternative'),
                    $queryBuilder->expr()->eq('alternative', $queryBuilder->createNamedParameter(''))
                )
            )
            ->executeQuery()
            ->fetchAllAssociative();

        if (count($rows) > 0) {
            $io->error(sprintf(
                'Accessibility-Audit fehlgeschlagen: %d Bild(er) ohne Alt-Text gefunden.',
                count($rows)
            ));

            $io->section('Details:');
            $tableRows = [];
            foreach ($rows as $row) {
                $tableRows[] = [
                    $row['uid'],
                    $row['tablenames'] . ':' . $row['fieldname'],
                    $row['pid'],
                ];
            }
            $io->table(
                ['UID', 'Kontext', 'PID'],
                $tableRows
            );

            $io->warning('Bitte fügen Sie für alle aufgeführten Bilder einen Alt-Text hinzu oder markieren Sie sie als dekorativ.');

            return Command::FAILURE;
        }

        $io->success('Accessibility-Audit: OK – alle relevanten Bilder haben Alt-Texte oder sind als dekorativ markiert.');
        return Command::SUCCESS;
    }
}









