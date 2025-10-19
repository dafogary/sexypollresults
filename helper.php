<?php

/**
 * @package     Joomla.Plugin
 * @subpackage  Content.SexyPollResults
 * @author      Gary Foster - DAFO Creative Ltd/LLC
 * @since       0.0.8 Alpha
 */

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Uri\Uri;

class PlgContentSexyPollResultsHelper
{
    
    public static function renderResults($pollId, $month, $type = 'bar', $colorStart = '#8e2de2', $colorEnd = '#4acfd9', $pieSize = 320, $showLabels = 1)
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
                $label = $r->option_text; // Allow HTML to render properly

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
            $labelsTrimmed = []; // For canvas drawing (HTML stripped)
            $labelsHtml = []; // For tooltips and legend (HTML preserved)
            $data = [];
            foreach ($results as $r) {
                $labels[] = $r->option_text; // Original for backward compatibility
                $labelsTrimmed[] = strip_tags($r->option_text); // For canvas text
                $labelsHtml[] = $r->option_text; // For HTML rendering
                $data[] = (int)$r->votes;
            }

            $labelsJson = json_encode($labels);
            $labelsTrimmedJson = json_encode($labelsTrimmed);
            $labelsHtmlJson = json_encode($labelsHtml);
            $dataJson = json_encode($data);
            $colors = json_encode([$colorStart, $colorEnd, '#fc67fa', '#43e97b', '#38f9d7']);

            $html .= '<div class="text-center" style="display: flex; justify-content: center; align-items: center; margin: 20px 0;">';
            $html .= '<canvas id="' . $chartId . '" width="' . $pieSize . '" height="' . $pieSize . '" style="max-width:' . $pieSize . 'px;max-height:' . $pieSize . 'px; display: block;"></canvas>';
            $html .= '</div>';
            
            // Add custom HTML legend that supports links (only if showLabels is disabled)
            if (!$showLabelsBoolean) {
                $html .= '<div class="pie-legend mt-3" style="display: flex; flex-wrap: wrap; justify-content: center; gap: 15px;">';
                foreach ($results as $index => $r) {
                    $percent = $total > 0 ? round(($r->votes / $total) * 100, 1) : 0;
                    $colorIndex = $index % 5; // Cycle through available colors
                    $legendColor = ['#8e2de2', '#4acfd9', '#fc67fa', '#43e97b', '#38f9d7'][$colorIndex];
                    
                    $html .= '<div class="legend-item" style="display: flex; align-items: center; margin-bottom: 8px;">';
                    $html .= '<span class="legend-color" style="display: inline-block; width: 12px; height: 12px; background-color: ' . $legendColor . '; border-radius: 50%; margin-right: 8px;"></span>';
                    $html .= '<span class="legend-text" style="font-size: 12px; color: #444;">' . $r->option_text . '</span>';
                    $html .= '</div>';
                }
                $html .= '</div>';
            }

            // Determine label configuration based on showLabels parameter
            // Convert to proper boolean - showLabels can be 0, 1, true, false
            $showLabelsBoolean = (bool)$showLabels;
            $labelConfig = $showLabelsBoolean ? 'true' : 'false';
            
            // Debug output (will appear in HTML comments)
            $html .= '<!-- SexyPollResults Debug: showLabels=' . $showLabels . ', boolean=' . ($showLabelsBoolean ? 'true' : 'false') . ' -->';
            
            $html .= '<script>
            document.addEventListener("DOMContentLoaded", function() {
                const ctx = document.getElementById("' . $chartId . '");
                const showLabels = ' . $labelConfig . ';
                
                const labelsHtml = ' . $labelsHtmlJson . ';
                const labelsTrimmed = ' . $labelsTrimmedJson . ';
                
                console.log("SexyPollResults Debug - showLabels:", showLabels);
                
                const config = {
                    type: "pie",
                    data: {
                        labels: labelsTrimmed, // Use stripped labels for chart
                        datasets: [{
                            data: ' . $dataJson . ',
                            backgroundColor: ' . $colors . ',
                            borderWidth: 2,
                            borderColor: "#fff"
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        layout: {
                            padding: showLabels ? 80 : 10 // Further increased padding for multi-line labels
                        },
                        elements: {
                            arc: {
                                // Make pie smaller when external labels are enabled
                                radius: showLabels ? "45%" : "80%"
                            }
                        },
                        plugins: {
                            legend: {
                                display: false // We\'ll create a custom HTML legend
                            },
                            tooltip: {
                                callbacks: {
                                    label: function(context) {
                                        const htmlLabel = labelsHtml[context.dataIndex];
                                        const value = context.parsed;
                                        const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                        const percent = ((value / total) * 100).toFixed(1);
                                        // Strip HTML for tooltip display but show the text content
                                        const labelText = htmlLabel.replace(/<[^>]*>/g, "");
                                        return labelText + ": " + value + " (" + percent + "%)";
                                    }
                                }
                            }
                        }
                    }
                };
                
                // Create chart instance
                let chart;
                
                // Only add external labels plugin if showLabels is enabled
                if (showLabels) {
                    console.log("Adding external labels plugin");
                    // Add external labels with leader lines
                    config.plugins = [{
                        id: "customLabels",
                        afterDraw: function(chart) {
                            const ctx = chart.ctx;
                            const meta = chart.getDatasetMeta(0);
                            const total = chart.data.datasets[0].data.reduce((a, b) => a + b, 0);
                            
                            meta.data.forEach((element, index) => {
                                const model = element;
                                const midAngle = (element.startAngle + element.endAngle) / 2;
                                const radius = element.outerRadius;
                                
                                // Dynamic label distance based on pie size and position
                                const baseDistance = Math.max(40, radius * 0.6);
                                const labelRadius = radius + baseDistance;
                                const lineRadius = radius + 12;
                                
                                // Calculate base label position
                                let x = Math.cos(midAngle) * labelRadius + element.x;
                                let y = Math.sin(midAngle) * labelRadius + element.y;
                                
                                // Determine text alignment based on position
                                const isLeftSide = x < element.x;
                                const isRightSide = x > element.x;
                                
                                // Adjust position to avoid canvas edges with proper margins for text
                                const canvasWidth = chart.width;
                                const canvasHeight = chart.height;
                                const textMargin = 120; // Space needed for text
                                const edgeMargin = 15;
                                
                                // For left side labels, ensure space for text width
                                if (isLeftSide) {
                                    x = Math.max(textMargin, x);
                                } else if (isRightSide) {
                                    x = Math.min(canvasWidth - textMargin, x);
                                }
                                
                                y = Math.max(edgeMargin + 30, Math.min(canvasHeight - edgeMargin - 50, y));
                                
                                // Calculate line start position
                                const lineStartX = Math.cos(midAngle) * lineRadius + element.x;
                                const lineStartY = Math.sin(midAngle) * lineRadius + element.y;
                                
                                // Calculate intermediate point for better line direction
                                const originalX = Math.cos(midAngle) * labelRadius + element.x;
                                const intermediateX = originalX;
                                const intermediateY = y;
                                
                                // Draw leader line with proper direction
                                ctx.beginPath();
                                ctx.moveTo(lineStartX, lineStartY);
                                // First draw to the natural position
                                ctx.lineTo(intermediateX, intermediateY);
                                // Then draw horizontal line to final position if needed
                                if (Math.abs(x - intermediateX) > 10) {
                                    ctx.lineTo(x, y);
                                }
                                ctx.strokeStyle = "#666";
                                ctx.lineWidth = 1;
                                ctx.stroke();
                                
                                // Prepare label text (use trimmed labels for canvas)
                                const value = chart.data.datasets[0].data[index];
                                const percent = ((value / total) * 100).toFixed(1);
                                const label = labelsTrimmed[index]; // Use HTML-stripped version
                                
                                // Split label with smart wrapping
                                let labelLines = [];
                                if (label.includes("Read more")) {
                                    const parts = label.split(/Read more[\.!]?/i);
                                    let mainText = parts[0].trim();
                                    
                                    // Wrap long main text
                                    if (mainText.length > 20) {
                                        const words = mainText.split(" ");
                                        let currentLine = "";
                                        words.forEach(word => {
                                            if ((currentLine + word).length > 18) {
                                                if (currentLine) labelLines.push(currentLine.trim());
                                                currentLine = word + " ";
                                            } else {
                                                currentLine += word + " ";
                                            }
                                        });
                                        if (currentLine.trim()) labelLines.push(currentLine.trim());
                                    } else {
                                        labelLines.push(mainText);
                                    }
                                    labelLines.push("Read more.");
                                } else {
                                    // Wrap long labels without "Read more"
                                    if (label.length > 20) {
                                        const words = label.split(" ");
                                        let currentLine = "";
                                        words.forEach(word => {
                                            if ((currentLine + word).length > 18) {
                                                if (currentLine) labelLines.push(currentLine.trim());
                                                currentLine = word + " ";
                                            } else {
                                                currentLine += word + " ";
                                            }
                                        });
                                        if (currentLine.trim()) labelLines.push(currentLine.trim());
                                    } else {
                                        labelLines.push(label);
                                    }
                                }
                                labelLines.push("(" + percent + "%)");
                                
                                // Set text properties - smaller font for better fit
                                ctx.fillStyle = "#333";
                                ctx.font = "11px Arial";
                                ctx.textAlign = isRightSide ? "left" : "right";
                                ctx.textBaseline = "middle";
                                
                                // Calculate dimensions for multi-line text
                                const lineHeight = 13;
                                const totalHeight = labelLines.length * lineHeight;
                                let maxWidth = 0;
                                
                                labelLines.forEach(line => {
                                    const metrics = ctx.measureText(line);
                                    maxWidth = Math.max(maxWidth, metrics.width);
                                });
                                
                                const padding = 4;
                                const startY = y - (totalHeight / 2) + (lineHeight / 2);
                                
                                // Add background to text for better readability
                                ctx.fillStyle = "rgba(255, 255, 255, 0.9)";
                                const rectX = ctx.textAlign === "left" ? 
                                    x - padding : 
                                    x - maxWidth - padding;
                                    
                                ctx.fillRect(
                                    rectX,
                                    startY - (lineHeight / 2) - padding,
                                    maxWidth + (padding * 2),
                                    totalHeight + (padding * 2)
                                );
                                
                                // Draw each line of text
                                ctx.fillStyle = "#333";
                                labelLines.forEach((line, lineIndex) => {
                                    const lineY = startY + (lineIndex * lineHeight);
                                    ctx.fillText(line, x, lineY);
                                });
                            });
                        }
                    }];
                } else {
                    console.log("External labels disabled");
                }
                
                chart = new Chart(ctx, config);
            });
            </script>';
        }

        $html .= '</div>';
        return $html;
    }
}
