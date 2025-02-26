<?php declare(strict_types=1);
class Psr4AutoloaderClass
{
    public function __construct(protected array $prefixedBaseDirs = [])
    {
    }

    public function registerNewAutoloader(): void
    {
        spl_autoload_register([$this, 'loadClassFromFQCN']);
    }

    public function addPrefixedBaseDirectoryForThisClass(string $namespacePrefix, string $baseDirectory): void
    {
        $namespacePrefix = trim($namespacePrefix, '\\') . '\\';
        $baseDirectory = rtrim($baseDirectory, DIRECTORY_SEPARATOR) . '/';
        $this->prefixedBaseDirs[$namespacePrefix] ??= [];
        array_push($this->prefixedBaseDirs[$namespacePrefix], $baseDirectory);
    }

    public function loadClassFromFQCN(string $FQCN): bool|string
    {
        $namespacePrefix = $FQCN;
        $lastBackslash = strrpos($namespacePrefix, '\\');

        while ($lastBackslash !== false) {
            $startFromBeginningOfString = 0;
            $endAtLastBackslash = $lastBackslash + 1;
            $namespacePrefix = substr($FQCN, $startFromBeginningOfString, $endAtLastBackslash);

            $startFqcnFromLastBackslash = $lastBackslash + 1;
            $classWithPossiblePostfix   = substr($FQCN, $startFqcnFromLastBackslash);

            $fileName = $this->loadFile($namespacePrefix, $classWithPossiblePostfix);
            if ($fileName) {
                return $fileName;
            }

            $namespacePrefix = rtrim($namespacePrefix, '\\');
            $lastBackslash   = strrpos($namespacePrefix, '\\');
        }

        return false;
    }

    protected function loadFile(string $namespacePrefix, string $classWithPossiblePostfix): bool|string
    {
        // Check if we've saved the current $namespacePrefix with $this->addPrefixedBaseDirectoryForThisClass()
        // in the protected $prefixedBaseDirs array:
        if (!isset($this->prefixedBaseDirs[$namespacePrefix])) {
            return false;
        }

        // If we have, then look through base directories for this namespace prefix:
        foreach ($this->prefixedBaseDirs[$namespacePrefix] as $prefixedBaseDir) {
            // Glue together the whole name of the class in the filesystem:
            $file = $prefixedBaseDir .
                    str_replace('\\', '/', $classWithPossiblePostfix) .
                    '.php';

            // It's possible that we added the class in $this->addPrefixedBaseDirectoryForThisClass(),
            // but the directories and / or class doesn't actually exist in the filesystem:
            if (file_exists($file)) {
                require $file;
                return $file;
            }
        }

        return false;
    }
}

$autoloader = new \Psr4AutoloaderClass;
$autoloader->registerNewAutoloader();
$namespacePrefix = '\\App\\';
$baseDirWithPrefix = __DIR__;
$autoloader->addPrefixedBaseDirectoryForThisClass($namespacePrefix, $baseDirWithPrefix . '/src');
// $autoloader->addPrefixedBaseDirectoryForThisClass($namespacePrefix, $baseDirWithPrefix . '/tests');