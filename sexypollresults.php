<?php
/**
 * @package     Joomla.Plugin
 * @subpackage  Content.SexyPollResults
 * @author      Gary Foster - DAFO Creative Ltd/LLC
 * @since       0.0.3 Alpha
 */

defined('_JEXEC') or die;

use Joomla\CMS\Plugin\CMSPlugin;

class PlgContentSexyPollResults extends CMSPlugin
{
    public function onContentPrepare($context, &$article, &$params, $page = 0)
    {
        if (strpos($article->text, '{sexyresults') === false) {
            return;
        }

        $regex = '/\{sexyresults\s+poll=([0-9]+)\s+month=([0-9]{4}-[0-9]{2})(?:\s+type=(bar|pie))?\}/i';

        require_once __DIR__ . '/helper.php';

        // Load plugin defaults
        $defaultType  = $this->params->get('default_type', 'bar');
        $colorStart   = $this->params->get('color_start', '#8e2de2');
        $colorEnd     = $this->params->get('color_end', '#4acfd9');
        $pieSize      = (int) $this->params->get('pie_size', 320);
        $showLabels   = (int) $this->params->get('show_labels', 1);

        $article->text = preg_replace_callback(
            $regex,
            function ($matches) use ($defaultType, $colorStart, $colorEnd, $pieSize, $showLabels) {
                $pollId = (int) $matches[1];
                $month  = $matches[2];
                $type   = isset($matches[3]) && in_array(strtolower($matches[3]), ['bar', 'pie'])
                    ? strtolower($matches[3])
                    : $defaultType;

                return PlgContentSexyPollResultsHelper::renderResults(
                    $pollId,
                    $month,
                    $type,
                    $colorStart,
                    $colorEnd,
                    $pieSize,
                    $showLabels
                );
            },
            $article->text
        );
    }
}
