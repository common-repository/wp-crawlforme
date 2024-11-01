<?php

/**
 * CrawlForMe Very simple graphic rendering (same as in the report)
 *
 * @package wp-crawlforme
 * @link http://wordpress.org/plugins/wp-crawlforme/
 * @author CrawlForMe <support@crawlforme.com>
 * @copyright CrawlForMe, 2014
 */
class wp_crawlforme_graph {
	
	public function __construct($stats) {
		$successfulRatio = 	$stats->successful == 0 ? 	0 : floor(($stats->successful / $stats->total) * 100); 
		$errorRatio = 		$stats->error == 0 ? 		0 : floor(($stats->error / $stats->total) * 100); 
		$redirectedRatio = 	$stats->redirected == 0 ? 	0 : floor(($stats->redirected / $stats->total) * 100); 
		$ignoredRatio = 	$stats->ignored == 0 ? 		0 : floor(($stats->ignored / $stats->total) * 100);
		?>
		<div id="resourceStatus-fixed-graph" style="text-align: left;">
			<table style="background-color: white; border: 0px; width: 100%; table-layout: fixed; font-size: 13px; text-align: left; border: 1px solid rgb(204, 204, 204);">
				<thead>
					<tr>
						<th style="width: 80px; "></th>
						<th style="width: 50px; "></th>
						<th style="width: 50px; "></th>
						<th></th>
					</tr>
				</thead>
				<tbody>
					<tr>
						<td>&nbsp;<?php _e('Total', wp_crawlforme::ID); ?></td>
						<td>100 %</td>
						<td><?php echo $total; ?></td>
						<td style="width: 0px; height: 0px; padding-right: 5px;"><table style="background-color: #A0A0A0; border: 1px solid #727272; width: 100%; height: 10px; "><tbody><tr><td></td></tr></tbody></table></td>
					</tr>
					<tr><td colspan="4" style="border-bottom: 1px solid #AAAAAA;"></td></tr>
					<tr>
						<td>&nbsp;<?php _e('Successful', wp_crawlforme::ID); ?></td>
						<td><?php echo $successfulRatio; ?> %</td>
						<td><?php echo $stats->successful; ?></td>
						<td style="width: 0px; height: 0px; padding-right: 5px;">
							<?php if($stats->successful > 0){ ?>
								<table style="background-color: #44B300; border: 1px solid #338600; width: <?php echo $successfulRatio; ?>%; height: 10px; "><tbody><tr><td></td></tr></tbody></table>
							<?php } ?>
							</td>
					</tr>
					<tr>
						<td>&nbsp;<?php _e('Error', wp_crawlforme::ID); ?></td>
						<td><?php echo $errorRatio; ?> %</td>
						<td><?php echo $stats->error; ?></td>
						<td style="width: 0px; height: 0px; padding-right: 5px;">
							<?php if($stats->error > 0){ ?>
								<table style="background-color: #D42700; border: 1px solid #971C00; width: <?php echo $errorRatio; ?>%; height: 10px; "><tbody><tr><td></td></tr></tbody></table>
							<?php } ?>
						</td>
					</tr>
					<tr>
						<td>&nbsp;<?php _e('Ignored', wp_crawlforme::ID); ?></td>
						<td><?php echo $ignoredRatio; ?> %</td>
						<td><?php echo $stats->ignored; ?></td>
						<td style="width: 0px; height: 0px; padding-right: 5px;">
							<?php if($stats->ignored > 0){ ?>
								<table style="background-color: #F2EF13; border: 1px solid #e3c500; width: <?php echo $ignoredRatio; ?>%; height: 10px; "><tbody><tr><td></td></tr></tbody></table>
							<?php } ?>
						</td>
					</tr>
					<tr>
						<td>&nbsp;<?php _e('Redirected', wp_crawlforme::ID); ?></td>
						<td><?php echo $redirectedRatio; ?> %</td>
						<td><?php echo $stats->redirected; ?></td>
						<td style="width: 0px; height: 0px; padding-right: 5px;">
							<?php if($stats->redirected > 0){ ?>
								<table style="background-color: #009FD4; border: 1px solid #00779f; width: <?php echo $redirectedRatio; ?>%; height: 10px; "><tbody><tr><td></td></tr></tbody></table>
							<?php } ?>
						</td>
					</tr>
				</tbody>
				<tfoot>
					<tr><td colspan="4" style="border-bottom: 1px solid #AAAAAA;"></td></tr>
					<tr><td colspan="4" style="text-align: right; color: #CCCCCC; font-style: italic;"><?php _e('Unit expressed in unique links', wp_crawlforme::ID); ?></td></tr>
				</tfoot>
			</table>
		</div>
		<?php
	}
}
?>