<?php

/**
 * @package     Joomla.Plugin
 * @subpackage  Content.SexyPollResults
 * @author      Gary Foster - DAFO Creative Ltd/LLC
 * @since       0.0.3 Alpha
 */

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Uri\Uri;

class PlgContentSexyPollResultsHelper
{
    
    public static function renderResults($pollId, $month, $type = 'bar', $colorStart = '#8e2de2', $colorEnd = '#4acfd9')
    {
        $db = Factory::getDbo();
        list($year, $mon) = explode('-', $month);

        // ✅ Query with poll question included
        $query = $db->getQuery(true)
            ->select(
                $db->quoteName('a.name', 'option_text') . ', ' .
                $db->quoteName('p.question', 'poll_question') . ', ' .
                'COUNT(' . $db->quoteName('v.id_vote') . ') AS ' . $db->quoteName('votes')
            )
            ->from($db->quoteName('#__sexy_votes', 'v'))
            ->join(
                'INNER',
                $db->quoteName('#__sexy_answers', 'a') .
                ' ON ' . $db->quoteName('a.id') . ' = ' . $db->quoteName('v.id_answer')
            )
            ->join(
                'INNER',
                $db->quoteName('#__sexy_polls', 'p') .
                ' ON ' . $db->quoteName('p.id') . ' = ' . $db->quoteName('a.id_poll')
            )
            ->where($db->quoteName('a.id_poll') . ' = ' . (int) $pollId)
            ->where('YEAR(' . $db->quoteName('v.date') . ') = ' . (int) $year)
            ->where('MONTH(' . $db->quoteName('v.date') . ') = ' . (int) $mon)
            ->group($db->quoteName('v.id_answer'))
            ->order($db->quoteName('votes') . ' DESC');

        $db->setQuery($query);
        $results = $db->loadObjectList();

        if (!$results) {
            return '<div class="sexy-poll-results alert alert-info">'
                . Text::_('No results found for this month.')
                . '</div>';
        }

        $pollQuestion = htmlspecialchars($results[0]->poll_question ?? '');
        $total = array_sum(array_column($results, 'votes'));
        HTMLHelper::_('bootstrap.loadCss', true);

        // ✅ Load Chart.js for pie type
        if ($type === 'pie') {
            $doc = Factory::getDocument();
            $doc->addScript('https://cdn.jsdelivr.net/npm/chart.js');
        }

        // ✅ Generate HTML output
        $html  = '<div class="sexy-poll-results mb-4">';
        $html .= '<h4 class="mb-1">' . $pollQuestion . '</h4>';
        $html .= '<p class="text-muted mb-3">' . Text::_('Results for ') . htmlspecialchars($month) . '</p>';
        
        if ($type === 'bar') {
            // 🔹 Gradient Bar Layout
            $html .= '<style>
                .progress-bar-gradient {
                    background: linear-gradient(90deg, ' . $colorStart . ', ' . $colorEnd . ');
                }
            </style>';


            foreach ($results as $r) {
                $percent = $total > 0 ? round(($r->votes / $total) * 100, 1) : 0;
                $label = htmlspecialchars($r->option_text);

                $html .= '<div class="mb-2">';
                $html .= '<div class="d-flex justify-content-between">';
                $html .= '<span><strong>' . $label . '</strong></span>';
                $html .= '<span>' . (int)$r->votes . ' (' . $percent . '%)</span>';
                $html .= '</div>';
                $html .= '<div class="progress" style="height: 22px;">';
                $html .= '<div class="progress-bar progress-bar-gradient" role="progressbar" ';
                $html .= 'style="width:' . $percent . '%;" ';
                $html .= 'aria-valuenow="' . $percent . '" aria-valuemin="0" aria-valuemax="100">';
                $html .= '</div></div></div>';
            }
        } elseif ($type === 'pie') {
            // ✅ Pie Chart Layout with labels overlay
            $chartId = 'poll_chart_' . uniqid();
            $labels = [];
            $data = [];
            foreach ($results as $r) {
                $labels[] = $r->option_text;
                $data[] = (int)$r->votes;
            }

            $labelsJson = json_encode($labels);
            $dataJson = json_encode($data);
            $colors = json_encode([$colorStart, $colorEnd, '#fc67fa', '#43e97b', '#38f9d7']);

            // Load Chart.js + datalabels plugin
            $doc = Factory::getDocument();
            $doc->addScript('https://cdn.jsdelivr.net/npm/chart.js');
            $doc->addScript('https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels');

            $html .= '<div class="text-center">';
            $html .= '<canvas id="' . $chartId . '" width="320" height="320" style="max-width:320px;max-height:320px;"></canvas>';
            $html .= '</div>';

            $html .= '<script>
            document.addEventListener("DOMContentLoaded", function() {
                const ctx = document.getElementById("' . $chartId . '");
                new Chart(ctx, {
                    type: "pie",
                    data: {
                        labels: ' . $labelsJson . ',
                        datasets: [{
                            data: ' . $dataJson . ',
                            backgroundColor: ' . $colors . ',
                            borderWidth: 1
                        }]
                    },
                    options: {
                        plugins: {
                            legend: {
                                position: "bottom",
                                labels: { color: "#444", font: { size: 14 } }
                            },
                            datalabels: {
                                color: "#fff",
                                formatter: (value, ctx) => {
                                    let label = ctx.chart.data.labels[ctx.dataIndex];
                                    let total = ctx.chart.data.datasets[0].data.reduce((a,b)=>a+b,0);
                                    let percent = ((value / total) * 100).toFixed(1) + "%";
                                    return label + "\\n" + percent;
                                },
                                font: {
                                    weight: "bold",
                                    size: 12
                                }
                            }
                        }
                    },
                    plugins: [ChartDataLabels]
                });
            });
            </script>';
        }

        $html .= '</div>';
        return $html;
    }
}
