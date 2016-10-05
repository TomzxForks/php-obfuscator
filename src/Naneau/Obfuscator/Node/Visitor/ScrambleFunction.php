<?php

namespace Naneau\Obfuscator\Node\Visitor;

use Naneau\Obfuscator\Node\Visitor\Scrambler as ScramblerVisitor;
use Naneau\Obfuscator\ScopedStringScrambler;
use PhpParser\Node;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\Stmt\Function_;
use PhpParser\NodeTraverser;

class ScrambleFunction extends ScramblerVisitor
{
    use TrackingRenamerTrait;
    use SkipTrait;

    /**
     * @param  Node $node
     * @return void
     **/
    public function enterNode(Node $node)
    {
        if ($this->shouldSkip()) {
            return;
        }

        if ($node instanceof Function_ || $node instanceof ClassMethod) {
            $traverser = new NodeTraverser();
            $traverser->addVisitor(new ScrambleVariable(new ScopedStringScrambler()));

            $traverser->traverse([$node]);
        }
    }
}

