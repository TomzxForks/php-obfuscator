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
     * @param array $options
     */
    public function __construct(array $options = []) {
        parent::__construct($options);
        $this->keepIndentToken = '_KEEP_INDENT_' . mt_rand();
    }

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
        $this->preprocessNodes($stmts);

        $output = $this->pStmts($stmts, false);
        $output = $this->removeFormatting($output);
        $output = $this->handleMagicTokens($output);

        return $output;
    }

    /**
     * @param \PhpParser\Node\Expr $node
     * @return string
     */
    public function prettyPrintExpr(Expr $node)
    {
        $output = $this->p($node);
        $output = $this->removeFormatting($output);
        $output = $this->handleMagicTokens($output);

        return $output;
    }

    protected function handleMagicTokens($str)
    {
        if ($this->keepFormatting) {
            $str = str_replace($this->keepIndentToken, "", $str);
        } else {
            $str = str_replace($this->keepIndentToken, "\n", $str);
            // If we lose the formatting, we still need the heredoc to render properly
            $str = str_replace($this->noIndentToken, "\n", $str);
        }

        return parent::handleMagicTokens($str);
    }

    protected function removeFormatting($string)
    {
        if ($this->keepFormatting) {
            return $string;
        }

        $string = preg_replace("/\r\n|\n/", '', $string);
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
     * @return string|null
     */
    protected function pComments(array $comments)
    {
        // Remove comments
        if ( ! $this->keepComments) {
            return;
        }

        $formattedComments = [];

        foreach ($comments as $comment) {
            $formattedComments[] = preg_replace('/\r\n|\n/', $this->keepIndentToken."\n", $comment->getReformattedText());
        }

        return $this->keepIndentToken.implode($this->keepIndentToken, $formattedComments).$this->keepIndentToken;
    }

}
