<?php
/**
 * Obfuscator.php
 *
 * @package         Obfuscator
 * @subpackage      Obfuscator
 */

namespace Naneau\Obfuscator;

use Naneau\Obfuscator\Obfuscator\Event\File as FileEvent;
use Naneau\Obfuscator\Obfuscator\Event\FileError as FileErrorEvent;

use PhpParser\NodeTraverserInterface as NodeTraverser;

use PhpParser\Parser;
use PhpParser\Lexer;
use PhpParser\PrettyPrinter\Standard as PrettyPrinter;

use Symfony\Component\EventDispatcher\EventDispatcher;

use \RegexIterator;
use \RecursiveDirectoryIterator;
use \RecursiveIteratorIterator;
use \SplFileInfo;

use \Exception;

/**
 * Obfuscator
 *
 * Obfuscates a directory of files
 *
 * @category        Naneau
 * @package         Obfuscator
 * @subpackage      Obfuscator
 */
class Obfuscator
{
    /**
     * the parser
     *
     * @var Parser
     */
    private $parser;

    /**
     * the node traverser
     *
     * @var NodeTraverser
     */
    private $traverser;

    /**
     * the "pretty" printer
     *
     * @var PrettyPrinter
     */
    private $prettyPrinter;

    /**
     * the event dispatcher
     *
     * @var EventDispatcher
     */
    private $eventDispatcher;

    /**
     * The file regex
     *
     * @var string
     **/
    private $fileRegex = '/\.php$/';

    /**
     * Strip whitespace
     *
     * @param  string $directory
     * @param array $options
     */
    public function obfuscate($directory, array $options)
    {
        foreach ($this->getFiles($directory) as $file) {
            $this->getEventDispatcher()->dispatch(
                'obfuscator.file',
                new FileEvent($file)
            );

            // Write obfuscated source
            file_put_contents($file, $this->obfuscateFileContents($file, $options));
        }
    }

    /**
     * Get the parser
     *
     * @return Parser
     */
    public function getParser()
    {
        return $this->parser;
    }

    /**
     * Set the parser
     *
     * @param  Parser     $parser
     * @return Obfuscator
     */
    public function setParser(Parser $parser)
    {
        $this->parser = $parser;

        return $this;
    }

    /**
     * Get the node traverser
     *
     * @return NodeTraverser
     */
    public function getTraverser()
    {
        return $this->traverser;
    }

    /**
     * Set the node traverser
     *
     * @param  NodeTraverser $traverser
     * @return Obfuscator
     */
    public function setTraverser(NodeTraverser $traverser)
    {
        $this->traverser = $traverser;

        return $this;
    }

    /**
     * Get the "pretty" printer
     *
     * @return PrettyPrinter
     */
    public function getPrettyPrinter()
    {
        return $this->prettyPrinter;
    }

    /**
     * Set the "pretty" printer
     *
     * @param  PrettyPrinter $prettyPrinter
     * @return Obfuscator
     */
    public function setPrettyPrinter(PrettyPrinter $prettyPrinter)
    {
        $this->prettyPrinter = $prettyPrinter;

        return $this;
    }

    /**
     * Get the event dispatcher
     *
     * @return EventDispatcher
     */
    public function getEventDispatcher()
    {
        return $this->eventDispatcher;
    }

    /**
     * Set the event dispatcher
     *
     * @param EventDispatcher $eventDispatcher
     * @return Obfuscator
     */
    public function setEventDispatcher(EventDispatcher $eventDispatcher)
    {
        $this->eventDispatcher = $eventDispatcher;

        return $this;
    }

    /**
     * Get the regex for file inclusion
     *
     * @return string
     */
    public function getFileRegex()
    {
        return $this->fileRegex;
    }

    /**
     * Set the regex for file inclusion
     *
     * @param string $fileRegex
     * @return Obfuscator
     */
    public function setFileRegex($fileRegex)
    {
        $this->fileRegex = $fileRegex;

        return $this;
    }

    /**
     * Get the file list
     *
     * @return SplFileInfo
     **/
    private function getFiles($directory)
    {
        return new RegexIterator(
            new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($directory)
            ),
            $this->getFileRegex()
        );
    }

    /**
     * Obfuscate a single file's contents
     *
     * @param  string $file
     * @return string obfuscated contents
     **/
    public function obfuscateFileContents($file, array $options = [])
    {
        try {
            $source = file_get_contents($file);

            return $this->obfuscateContent($source, $options);
        } catch (Exception $e) {
            if(isset($options['ignore_error'])) {
                sprintf('Could not parse file "%s"', $file);
                $this->getEventDispatcher()->dispatch(
                    'obfuscator.file.error',
                    new FileErrorEvent($file, $e->getMessage())
                );
            } else {
                throw new Exception(
                    sprintf('Could not parse file "%s"', $file),
                    null,
                    $e
                );
            }
        }
    }

    /**
     * @param string $source
     * @return string
     */
    public function obfuscateContent($source, array $options = [])
    {
        // Get AST
        $ast = $this->getTraverser()->traverse(
            $this->getParser()->parse($source)
        );

        $output = $this->getPrettyPrinter()
            ->setKeepComments(isset($options['keep_comments']) ? $options['keep_comments'] : false)
            ->setKeepFormatting(isset($options['keep_formatting']) ? $options['keep_formatting'] : false)
            ->prettyPrint($ast);

        return "<?php\n" . $output;
    }
}
