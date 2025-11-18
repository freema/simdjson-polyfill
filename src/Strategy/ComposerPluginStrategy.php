<?php

declare(strict_types=1);

namespace SimdJsonPolyfill\Strategy;

use PhpParser\Error;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitor\NameResolver;
use PhpParser\ParserFactory;
use PhpParser\PrettyPrinter\Standard;

/**
 * Composer Plugin strategy - rewrites vendor code at install time using AST manipulation.
 *
 * ⚠️ EXTREMELY RISKY: This strategy modifies third-party code in vendor/ directory.
 * Should only be used with explicit configuration, never auto-detected.
 * Creates backups before modification.
 */
final class ComposerPluginStrategy implements StrategyInterface
{
    private bool $enabled = false;

    public function isAvailable(): bool
    {
        return extension_loaded('simdjson') && class_exists(ParserFactory::class);
    }

    public function enable(array $config = []): void
    {
        if (!$this->isAvailable()) {
            throw new \RuntimeException(
                'ComposerPluginStrategy requires ext-simdjson and nikic/php-parser to be installed.'
            );
        }

        // This is extremely risky - require explicit confirmation
        $confirmed = $config['i_understand_the_risks'] ?? false;
        if (!$confirmed) {
            throw new \RuntimeException(
                'ComposerPluginStrategy requires explicit confirmation via ' .
                '"i_understand_the_risks" => true in configuration. ' .
                'This strategy will modify vendor code and may break your application.'
            );
        }

        $vendorDir = $config['vendor_dir'] ?? getcwd() . '/vendor';
        $excludePatterns = $config['exclude_patterns'] ?? ['*/tests/*', '*/Tests/*'];
        $createBackups = $config['create_backups'] ?? true;

        $this->rewriteVendorCode($vendorDir, $excludePatterns, $createBackups);
        $this->enabled = true;
    }

    public function decode(
        string $json,
        ?bool $associative = null,
        int $depth = 512,
        int $flags = 0
    ): mixed {
        // This strategy works by rewriting code, not providing runtime decode
        $polyfill = new PolyfillStrategy();
        return $polyfill->decode($json, $associative, $depth, $flags);
    }

    public function getName(): string
    {
        return 'composer-plugin';
    }

    public function getPriority(): int
    {
        return 0; // Never auto-detected, must be explicit
    }

    /**
     * Rewrite json_decode calls in vendor directory.
     *
     * @param string $vendorDir
     * @param array<string> $excludePatterns
     * @param bool $createBackups
     */
    private function rewriteVendorCode(
        string $vendorDir,
        array $excludePatterns,
        bool $createBackups
    ): void {
        if (!is_dir($vendorDir)) {
            throw new \RuntimeException("Vendor directory not found: {$vendorDir}");
        }

        $phpFiles = $this->findPhpFiles($vendorDir, $excludePatterns);
        $parser = (new ParserFactory())->createForNewestSupportedVersion();
        $prettyPrinter = new Standard();

        foreach ($phpFiles as $file) {
            try {
                $this->rewriteFile($file, $parser, $prettyPrinter, $createBackups);
            } catch (Error $e) {
                // Skip files that can't be parsed
                trigger_error(
                    "Skipping {$file}: Parse error - {$e->getMessage()}",
                    E_USER_WARNING
                );
            }
        }
    }

    /**
     * Find all PHP files in directory, excluding patterns.
     *
     * @param string $dir
     * @param array<string> $excludePatterns
     * @return array<string>
     */
    private function findPhpFiles(string $dir, array $excludePatterns): array
    {
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir, \RecursiveDirectoryIterator::SKIP_DOTS)
        );

        $files = [];
        foreach ($iterator as $file) {
            if ($file->getExtension() !== 'php') {
                continue;
            }

            $path = $file->getPathname();

            // Check exclude patterns
            $excluded = false;
            foreach ($excludePatterns as $pattern) {
                if (fnmatch($pattern, $path)) {
                    $excluded = true;
                    break;
                }
            }

            if (!$excluded) {
                $files[] = $path;
            }
        }

        return $files;
    }

    /**
     * Rewrite a single PHP file.
     *
     * @param string $file
     * @param \PhpParser\Parser $parser
     * @param Standard $prettyPrinter
     * @param bool $createBackup
     */
    private function rewriteFile(
        string $file,
        \PhpParser\Parser $parser,
        Standard $prettyPrinter,
        bool $createBackup
    ): void {
        $code = file_get_contents($file);
        if ($code === false) {
            return;
        }

        // Skip if already rewritten
        if (str_contains($code, 'SimdJsonPolyfill')) {
            return;
        }

        $stmts = $parser->parse($code);
        if ($stmts === null) {
            return;
        }

        // Use AST traverser to find and replace json_decode calls
        $traverser = new NodeTraverser();
        $traverser->addVisitor(new NameResolver());
        $visitor = new JsonDecodeRewriteVisitor();
        $traverser->addVisitor($visitor);

        $modifiedStmts = $traverser->traverse($stmts);

        // Only write if changes were made
        if (!$visitor->hasChanges()) {
            return;
        }

        // Create backup
        if ($createBackup) {
            $backupFile = $file . '.bak';
            if (!copy($file, $backupFile)) {
                throw new \RuntimeException("Failed to create backup: {$backupFile}");
            }
        }

        // Write modified code
        $modifiedCode = $prettyPrinter->prettyPrintFile($modifiedStmts);
        if (file_put_contents($file, $modifiedCode) === false) {
            throw new \RuntimeException("Failed to write modified file: {$file}");
        }
    }
}

/**
 * AST visitor that rewrites json_decode calls to use SimdJsonPolyfill.
 * This is a placeholder - full implementation would require nikic/php-parser traversal logic.
 */
class JsonDecodeRewriteVisitor extends \PhpParser\NodeVisitorAbstract
{
    private bool $hasChanges = false;

    public function hasChanges(): bool
    {
        return $this->hasChanges;
    }

    public function leaveNode(\PhpParser\Node $node): ?\PhpParser\Node
    {
        // Detect json_decode() function calls and rewrite them
        if ($node instanceof \PhpParser\Node\Expr\FuncCall) {
            if ($node->name instanceof \PhpParser\Node\Name) {
                $functionName = $node->name->toString();
                if ($functionName === 'json_decode') {
                    // Rewrite to \SimdJsonPolyfill\JsonDecoder::decode()
                    $this->hasChanges = true;
                    return new \PhpParser\Node\Expr\StaticCall(
                        new \PhpParser\Node\Name\FullyQualified('SimdJsonPolyfill\JsonDecoder'),
                        'decode',
                        $node->args
                    );
                }
            }
        }

        return null;
    }
}
