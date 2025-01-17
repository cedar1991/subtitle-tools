<?php

namespace App\Support;

class Tesseract
{
    /**
     * Path to the image to be recognized.
     *
     * @var string
     */
    private $image;

    /**
     * Path to tesseract executable.
     * Default value assumes it is present in the $PATH.
     *
     * @var string
     */
    private $executable = 'tesseract';

    /**
     * Path to tessdata directory.
     *
     * @var string
     */
    private $tessdataDir;

    /**
     * Path to user words file.
     *
     * @var string
     */
    private $userWords;

    /**
     * Path to user patterns file.
     *
     * @var string
     */
    private $userPatterns;

    /**
     * List of languages.
     *
     * @var array
     */
    private $languages = [];

    /**
     * Page Segmentation Mode value.
     *
     * @var integer
     */
    private $psm;

    /**
     * List of tesseract configuration variables.
     *
     * @var array
     */
    private $configs = [];

    /**
     * Quiet mode status.
     *
     * @var bool
     */
    private $statusQuietMode = false;

    /**
     * If true, all warnings and errors from tesseract will be suppressed.
     *
     * @var bool
     */
    private $suppressErrorsMode = false;

    /**
     * Class constructor.
     *
     * @param string $image
     * @return TesseractOCR
     */
    public function __construct($image)
    {
        $this->image = $image;
        return $this;
    }

    /**
     * Executes tesseract command and returns the generated output.
     *
     * @return string
     */
    public function run()
    {
        return trim(`{$this->buildCommand()}`);
    }

    /**
     * Sets a custom location for the tesseract executable.
     *
     * @param string $executable
     * @return $this
     */
    public function executable($executable)
    {
        $this->executable = $executable;
        return $this;
    }

    /**
     * Sets a custom tessdata directory.
     *
     * @param string $dir
     * @return TesseractOCR
     */
    public function tessdataDir($dir)
    {
        $this->tessdataDir = $dir;
        return $this;
    }

    /**
     * Sets user words file path.
     *
     * @param string $filePath
     * @return TesseractOCR
     */
    public function userWords($filePath)
    {
        $this->userWords = $filePath;
        return $this;
    }

    /**
     * Sets user patterns file path.
     *
     * @param string $filePath
     * @return TesseractOCR
     */
    public function userPatterns($filePath)
    {
        $this->userPatterns = $filePath;
        return $this;
    }

    /**
     * Sets the language(s).
     *
     * @param string ...$languages
     * @return $this
     */
    public function lang()
    {
        $this->languages = func_get_args();
        return $this;
    }

    /**
     * Sets the Page Segmentation Mode value.
     *
     * @param integer $psm
     * @return TesseractOCR
     */
    public function psm($psm)
    {
        $this->psm = $psm;
        return $this;
    }

    /**
     * Sets a tesseract configuration value.
     *
     * @param string $key
     * @param string $value
     * @return TesseractOCR
     */
    public function config($key, $value)
    {
        $this->configs[$key] = $value;
        return $this;
    }

    /**
     * Shortcut to set tessedit_char_whitelist values in a more convenient way.
     * Example:
     *
     *     (new WrapTesseractOCR('image.png'))
     *         ->whitelist(range(0, 9), range('A', 'F'), '-_@')
     *
     * @param mixed ...$charlists
     * @return TesseractOCR
     */
    public function whitelist()
    {
        $concatenate = function ($carry, $item) {
            return $carry . join('', (array)$item);
        };
        $whitelist = array_reduce(func_get_args(), $concatenate, '');
        $this->config('tessedit_char_whitelist', $whitelist);
        return $this;
    }

    /**
     * Change quiet mode state.
     *
     * @param bool $status
     * @return $this
     */
    public function quietMode($status = true)
    {
        $this->statusQuietMode = boolval($status);
        return $this;
    }

    /**
     * Change suppress errors mode state.
     *
     * @param bool $status
     * @return $this
     */
    public function suppressErrors($status = true)
    {
        $this->suppressErrorsMode = boolval($status);
        return $this;
    }

    /**
     * Builds the tesseract command with all its options.
     *
     * @return string
     */
    public function buildCommand()
    {
        return $this->executable.' '.escapeshellarg($this->image).' stdout'
            .$this->buildTessdataDirParam()
            .$this->buildUserWordsParam()
            .$this->buildUserPatternsParam()
            .$this->buildLanguagesParam()
            .$this->buildPsmParam()
            .$this->buildConfigurationsParam()
            .$this->buildQuietMode()
            .$this->buildSuppressErrorsMode();
    }

    /**
     * If tessdata directory is defined, return the correspondent command line
     * argument to the tesseract command.
     *
     * @return string
     */
    private function buildTessdataDirParam()
    {
        return $this->tessdataDir ? " --tessdata-dir $this->tessdataDir" : '';
    }

    /**
     * If user words file is defined, return the correspondent command line
     * argument to the tesseract command.
     *
     * @return string
     */
    private function buildUserWordsParam()
    {
        return $this->userWords ? " --user-words $this->userWords" : '';
    }

    /**
     * If user patterns file is defined, return the correspondent command line
     * argument to the tesseract command.
     *
     * @return string
     */
    private function buildUserPatternsParam()
    {
        return $this->userPatterns ? " --user-patterns $this->userPatterns" : '';
    }

    /**
     * If one (or more) languages are defined, return the correspondent command
     * line argument to the tesseract command.
     *
     * @return string
     */
    private function buildLanguagesParam()
    {
        return $this->languages ? ' -l '.join('+', $this->languages) : '';
    }

    /**
     * If a page segmentation mode is defined, return the correspondent command
     * line argument to the tesseract command.
     *
     * @return string
     */
    private function buildPsmParam()
    {
        return is_null($this->psm) ? '' : ' -psm '.$this->psm;
    }

    /**
     * Return tesseract command line arguments for every custom configuration.
     *
     * @return string
     */
    private function buildConfigurationsParam()
    {
        $buildParam = function ($config, $value) {
            return ' -c '.escapeshellarg("$config=$value");
        };
        return join('', array_map(
            $buildParam,
            array_keys($this->configs),
            array_values($this->configs)
        ));
    }

    /**
     * If quiet mode is defined, return the correspondent command line argument
     * to the tesseract command.
     *
     * @return string
     */
    private function buildQuietMode()
    {
        return $this->statusQuietMode ? ' quiet' : '';
    }

    /**
     * If suppress errors mode is defined, redirect all stderr output from the
     * tesseract command to /dev/null.
     *
     * @return string
     */
    private function buildSuppressErrorsMode()
    {
        return $this->suppressErrorsMode ? ' 2>/dev/null' : '';
    }
}
