<?php

namespace Qc\QcComments\Domain\Repository;

use TYPO3\CMS\Backend\Tree\View\PageTreeView;

/**
 * PageTreeView keeps the collected page ids in a protected property with no public
 * accessor; this subclass exposes them so CommentRepository can read them.
 */
class PageTreeIdCollector extends PageTreeView
{
    /**
     * @return array
     */
    public function getCollectedIds(): array
    {
        return $this->ids;
    }
}
