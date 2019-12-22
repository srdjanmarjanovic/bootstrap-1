<?php

/*
 * This file is part of the Active Collab Bootstrap project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\Bootstrap\Command\DevCommand;

use ActiveCollab\Bootstrap\Router\Retro\Nodes\DirectoryInterface;
use ActiveCollab\Bootstrap\Router\Retro\Nodes\NodeInterface;
use ActiveCollab\Bootstrap\Router\Retro\Router;
use ActiveCollab\Bootstrap\SitemapPathResolver\SitemapPathResolverInterface;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class SitemapCommand extends DevCommand
{
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $root  = (new Router())->scan($this->getSitemapPath());

        $table = new Table($output);
        $table->setHeaders(
            [
                'Path',
                'Type',
            ]
        );

        $this->recursivelyPopulateRows($root, '', $table);

        $table->render();
    }

    private function recursivelyPopulateRows(DirectoryInterface $directory, string $indent, Table $table): void
    {
        $table->addRow(
            [
                $this->getNodePath($directory, $indent),
                'dir',
            ]
        );

        foreach ($directory->getSubdirectories() as $subdirectory) {
            $this->recursivelyPopulateRows($subdirectory, $indent . '  ', $table);
        }

        foreach ($directory->getFiles() as $file) {
            $table->addRow(
                [
                    $this->getNodePath($file, $indent),
                    'file',
                ]
            );
        }
    }

    private function getNodePath(NodeInterface $node, string $indent): string
    {
        return $indent . '/' . $node->getNodePath();
    }

    protected function getSitemapPath(): string
    {
        return $this->getContainer()->get(SitemapPathResolverInterface::class)->getSitemapPath();
    }
}