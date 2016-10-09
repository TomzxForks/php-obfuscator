<?php

namespace Naneau\Obfuscator;

use PhpParser\Node\Expr;

class MinifyPrettyPrinter extends \PhpParser\PrettyPrinter\Standard
{
    /**
     * @var bool
     */
    protected $keepComments = false;
    /**
     * @var bool
     */
    protected $keepFormatting = false;

    /**
     * @param bool $keepComments
     * @return $this
     */
    public function setKeepComments($keepComments)
    {
        $this->keepComments = $keepComments;

        return $this;
    }

    /**
     * @param bool $keepFormatting
     * @return $this
     */
    public function setKeepFormatting($keepFormatting)
    {
        $this->keepFormatting = $keepFormatting;

        return $this;
    }

    /**
     * @param array $stmts
     * @return string
     */
    public function prettyPrint(array $stmts)
    {
        return $this->removeFormatting(parent::prettyPrint($stmts));
    }

    /**
     * @param \PhpParser\Node\Expr $node
     * @return string
     */
    public function prettyPrintExpr(Expr $node)
    {
        return $this->removeFormatting($this->handleMagicTokens($this->p($node)));
    }

    protected function removeFormatting($string)
    {
        if ($this->keepFormatting) {
            return str_replace('__MAGIC_LINE_SEPARATOR__', "\n", $string);
        }

        $string = str_replace("\n", '', $string);
        $string = str_replace('__MAGIC_LINE_SEPARATOR__', "\n", $string);
        return $string;
    }

    /**
     * @param array $nodes
     * @param bool $indent
     * @return string
     */
    protected function pStmts(array $nodes, $indent = null)
    {
        return parent::pStmts($nodes, $this->keepFormatting);
    }

    /**
     * @param array $comments
     * @return null
     */
    protected function pComments(array $comments)
    {
        // Remove comments
        if ( ! $this->keepComments) {
            return;
        }

        // Use a magic token so we can replace it after stripping the \n
        return parent::pComments($comments).'__MAGIC_LINE_SEPARATOR__';
    }

}
