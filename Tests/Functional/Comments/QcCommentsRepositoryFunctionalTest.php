<?php

declare(strict_types=1);

/***
 *
 * This file is part of Qc Comments project.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 *  (c) 2023 <techno@quebec.ca>
 *
 ***/

namespace Qc\QcCommentsTest\Tests\Functional\Comments;

use Doctrine\DBAL\Exception as DBALException;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Qc\QcComments\Domain\Filter\CommentsFilter;
use Qc\QcComments\Domain\Repository\CommentRepository;
use TYPO3\CMS\Core\Localization\LanguageServiceFactory;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

/**
 * @covers \Qc\QcComments\Domain\Repository\CommentRepository
 */
class QcCommentsRepositoryFunctionalTest extends FunctionalTestCase
{
    protected CommentRepository $commentRepository;

    protected array $coreExtensionsToLoad = [
        'backend',
        'beuser',
        'fluid',
        'info',
        'install',
        'core',
    ];

    protected array $testExtensionsToLoad = ['typo3conf/ext/qc_comments'];

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws DBALException
     */
    protected function setUp(): void
    {
        parent::setUp();
        $GLOBALS['LANG'] = $this->getContainer()->get(LanguageServiceFactory::class)->create('default');
        $this->commentRepository = $this->getContainer()->get(CommentRepository::class);
        $filter = new CommentsFilter();
        $filter->setDepth(10);
        $filter->setDateRange('userDefined');
        $filter->setStartDate('2021-07-08 00:00:00');
        $filter->setIncludeEmptyPages(true);
        $this->commentRepository->setFilter($filter);
    }

    /**
     * @test
     * @throws \TYPO3\TestingFramework\Core\Exception
     */
    public function getComments(): void
    {
        $this->importCSVDataSet(__DIR__ . '/../Fixtures/Comments/Comments.csv');
        $result = $this->commentRepository->getComments([1, 2], 100, 'DESC', true);
        self::assertSame(2, $result['count']);
        self::assertSame('Page 1', $result['rows'][1]['title']);
        self::assertSame('Positif comment', $result['rows'][1]['records'][0]['comment']);
        self::assertSame('2022-07-08 00:00:00', $result['rows'][1]['records'][0]['date_hour']);
        self::assertSame('1', $result['rows'][1]['records'][0]['useful']);
    }

    /**
     * @test
     * @throws \TYPO3\TestingFramework\Core\Exception
     */
    public function getStatistics(): void
    {
        $this->importCSVDataSet(__DIR__ . '/../Fixtures/Comments/Comments.csv');
        $rows = $this->commentRepository->getStatistics([1, 2], false);
        self::assertCount(2, $rows);

        $byPageUid = [];
        foreach ($rows as $row) {
            $byPageUid[(int)$row['page_uid']] = $row;
        }

        self::assertSame('Page 1', $byPageUid[1]['page_title']);
        self::assertSame(1, (int)$byPageUid[1]['total']);
        self::assertSame(1.0, (float)$byPageUid[1]['avg']);

        self::assertSame('Page 2', $byPageUid[2]['page_title']);
        self::assertSame(1, (int)$byPageUid[2]['total']);
        self::assertSame(0.0, (float)$byPageUid[2]['avg']);
    }
}
