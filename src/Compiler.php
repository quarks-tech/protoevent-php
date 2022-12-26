<?php

declare(strict_types=1);

namespace Quarks;

use Composer\Pcre\Preg;
use Symfony\Component\Finder\Finder;
use Seld\PharUtils\Timestamps;

class Compiler
{
    public function compile(string $pharFile = '../dist/protoc-gen-php-eventbus.phar'): void
    {
        if (file_exists($pharFile)) {
            unlink($pharFile);
        }

        $versionDate = new \DateTime();
        $versionDate->setTimezone(new \DateTimeZone('UTC'));

        $finderSort = static function ($a, $b): int {
            return strcmp(strtr($a->getRealPath(), '\\', '/'), strtr($b->getRealPath(), '\\', '/'));
        };

        $phar = new \Phar($pharFile, 0, 'protoc-gen-php-eventbus.phar');
        $phar->setSignatureAlgorithm(\Phar::SHA512);
        $phar->startBuffering();

        // Add vendor files
        $finder = new Finder();
        $finder->files()
            ->ignoreVCS(true)
            ->name('*.php')
            ->in(__DIR__ . '/../vendor/')
            ->sort($finderSort)
        ;

        foreach ($finder->files() as $file) {
            $this->addFile($phar, $file);
        }

        // Add source files
        $finder->files()
            ->ignoreVCS(true)
            ->path(['ClassesGenerator.php'])
            ->in(__DIR__ . '/../src/')
            ->sort($finderSort)
        ;

        foreach ($finder->files() as $file) {
            $this->addFile($phar, $file);
        }

        $this->addBin($phar);

        $phar->setStub($this->getStub());
        $phar->stopBuffering();

        $phar->compressFiles(\Phar::GZ);

        $util = new Timestamps($pharFile);
        $util->updateTimestamps($versionDate);
        $util->save($pharFile, \Phar::SHA512);
    }

    private function getStub(): string
    {
        return <<<'EOF'
#!/usr/bin/env php
<?php

Phar::mapPhar('protoc-gen-php-eventbus.phar');

require 'phar://protoc-gen-php-eventbus.phar/bin/protoc-gen-php-eventbus';
__HALT_COMPILER();
EOF;
    }

    private function addBin(\Phar $phar): void
    {
        $content = file_get_contents(__DIR__ . '/../bin/protoc-gen-php-eventbus');
        $content = Preg::replace('{^#!/usr/bin/env php\s*}', '', $content);

        $phar->addFromString('bin/protoc-gen-php-eventbus', $content);
    }

    private function addFile(\Phar $phar, \SplFileInfo $file): void
    {
        $path = $this->getRelativeFilePath($file);

        $content = file_get_contents((string) $file);

        $phar->addFromString($path, $content);
    }

    private function getRelativeFilePath(\SplFileInfo $file): string
    {
        $realPath = $file->getRealPath();
        $pathPrefix = dirname(__DIR__) . DIRECTORY_SEPARATOR;

        $pos = strpos($realPath, $pathPrefix);
        $relativePath = ($pos !== false) ? substr_replace($realPath, '', $pos, strlen($pathPrefix)) : $realPath;

        return strtr($relativePath, '\\', '/');
    }
}
