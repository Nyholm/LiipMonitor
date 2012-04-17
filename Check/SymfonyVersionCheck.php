<?php

namespace Liip\MonitorExtraBundle\Check;

use Symfony\Component\HttpKernel\Kernel;
use Liip\MonitorBundle\Check\Check;
use Liip\MonitorBundle\Exception\CheckFailedException;
use Liip\MonitorBundle\Result\CheckResult;

/**
 * Checks the version of this website against the latest stable release.
 *
 * Add this to your config.yml
 *
 *     monitor.check.symfony_version:
 *         class: Liip\MonitorExtraBundle\Check\SymfonyVersionCheck
 *         tags:
 *             - { name: monitor.check }
 *
 * @author Roderik van der Veer <roderik@vanderveer.be>
 */
class SymfonyVersionCheck extends Check
{

    /**
     * @see Liip\MonitorBundle\Check.CheckInterface::check()
     */
    public function check()
    {
        try {
            $latestRelease = $this->getLatestSymfonyVersion(); // eg. 2.0.12
            $currentVersion = Kernel::VERSION;
            if (version_compare($currentVersion, $latestRelease) >= 0) {
                $result = $this->buildResult('OK', CheckResult::OK);
            } else {
                $result = $this->buildResult('Update to ' . $latestRelease . ' from ' . $currentVersion, CheckResult::WARNING);
            }
        } catch (\Exception $e) {
            $result = $this->buildResult($e->getMessage(), CheckResult::UNKNOWN);
        }

        return $result;
    }

    private function getLatestSymfonyVersion()
    {
        $githubUser = 'symfony';
        $githubRepo = 'symfony';

        // Get GitHub JSON request

        $githubUrl = 'https://api.github.com/repos/' . $githubUser . '/' . $githubRepo . '/tags';
        $githubJSONResponse = file_get_contents($githubUrl);

        // Convert it to a PHP object

        $githubResponseArray = json_decode($githubJSONResponse, true);
        if (empty($githubResponseArray)) {
            throw new Exception("No valid response or no tags received from GitHub.");
        }

        $tags = array();

        foreach ($githubResponseArray as $tag) {
            $tags[] = $tag['name'];
        }

        // Sort tags

        natsort($tags);

        // Filter out non final tags

        $filteredTagList = array_filter($tags, function($tag)
        {
            return !stripos($tag, "PR");
        });

        // The first one is the last stable release for Symfony 2

        $reverseFilteredTagList = array_reverse($filteredTagList);

        return str_replace("v", "", $reverseFilteredTagList[0]);
    }

    /**
     * @see Liip\MonitorBundle\Check.Check::getName()
     */
    public function getName()
    {
        return 'Symfony version';
    }
}
