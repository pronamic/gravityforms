<tr stayle="background-color:#EAF2FA">
	<td colspan="2">
		<strong style="font-family: sans-serif; font-size:12px;"><?php echo esc_html( $order_summary['labels']['order_label'] ); ?></strong>
	</td>
</tr>
<tr style="background-color: #FFFFFF">
	<td style="width: 20px">&nbsp;</td>
	<td>
		<table cellspacing="0" style="border-left:1px solid #DFDFDF; border-top:1px solid #DFDFDF; width: 97%">
			<thead>
			<tr>
				<th style="background-color:#F4F4F4; border-bottom:1px solid #DFDFDF; border-right:1px solid #DFDFDF; padding:7px; font-family: sans-serif; font-size:12px; text-align:left"><?php echo esc_html( $order_summary['labels']['product'] ); ?></th>
				<th style="background-color:#F4F4F4; border-bottom:1px solid #DFDFDF; border-right:1px solid #DFDFDF; padding:7px; width:50px; font-family: sans-serif; font-size:12px; text-align:center"><?php echo esc_html( $order_summary['labels']['product_qty'] ); ?></th>
				<th style="background-color:#F4F4F4; border-bottom:1px solid #DFDFDF; border-right:1px solid #DFDFDF; padding:7px; width:155px; font-family: sans-serif; font-size:12px; text-align:left"><?php echo esc_html( $order_summary['labels']['product_unitprice'] ); ?></th>
				<th style="background-color:#F4F4F4; border-bottom:1px solid #DFDFDF; border-right:1px solid #DFDFDF; padding:7px; width:155px; font-family: sans-serif; font-size:12px; text-align:left"><?php echo esc_html( $order_summary['labels']['product_price'] ); ?></th>
			</tr>
			</thead>
			<tbody>
			<?php foreach ( rgars( $order_summary, 'rows/body', array() ) as $row ) { ?>
				<tr>
					<td style="border-bottom:1px solid #DFDFDF; border-right:1px solid #DFDFDF; padding:7px; font-family: sans-serif; font-size:11px;">
						<strong style="color:#BF461E; font-size:12px; margin-bottom:5px"><?php echo esc_html( rgar( $row, 'name' ) ); ?></strong>
							<ul style="margin:0">
						<?php if ( is_array( rgar( $row, 'options' ) ) ) { ?>
								<?php
								$count = sizeof( $row['options'] );
								for ( $i = 0; $i < $count; $i ++ ) {
									?>
									<li style="padding:4px 0 4px 0"><?php echo esc_html( rgar( $row['options'][ $i ], 'option_label' ) );?></li>
								<?php } ?>
						<?php } ?>
							</ul>
					</td>
					<td style="border-bottom:1px solid #DFDFDF; border-right:1px solid #DFDFDF; padding:7px; text-align:center; width:50px; font-family: sans-serif; font-size:11px;"><?php echo esc_html( rgar( $row, 'quantity', 1 ) ); ?></td>
					<td style="border-bottom:1px solid #DFDFDF; border-right:1px solid #DFDFDF; padding:7px; width:155px; font-family: sans-serif; font-size:11px;"><?php echo rgar( $row, 'price_money' ); ?></td>
					<td style="border-bottom:1px solid #DFDFDF; border-right:1px solid #DFDFDF; padding:7px; width:155px; font-family: sans-serif; font-size:11px;"><?php echo rgar( $row, 'sub_total_money' ); ?></td>
				</tr>
			<?php } ?>
			</tbody>
			<tfoot>
			<tr>
				<td colspan="2" style="background-color:#F4F4F4; border-bottom:1px solid #DFDFDF; border-right:1px solid #DFDFDF; padding:7px; font-size:11px;">&nbsp;</td>
				<td style="border-bottom:1px solid #DFDFDF; border-right:1px solid #DFDFDF; padding:7px; text-align:right; width:155px; font-family: sans-serif;">
					<strong style="font-size:12px;"><?php esc_html_e( 'Sub Total', 'gravityforms' ); ?></strong></td>
				<td style="border-bottom:1px solid #DFDFDF; border-right:1px solid #DFDFDF; padding:7px; width:155px; font-family: sans-serif;">
					<strong style="font-size:12px;"><?php echo $order_summary['totals']['sub_total_money']; ?></strong>
				</td>
			</tr>
			<?php foreach ( rgars( $order_summary, 'rows/footer', array() ) as $row ) { ?>
				<tr>
					<td colspan="2" style="background-color:#F4F4F4; border-bottom:1px solid #DFDFDF; border-right:1px solid #DFDFDF; padding:7px; font-size:11px;">&nbsp;</td>
					<td style="border-bottom:1px solid #DFDFDF; border-right:1px solid #DFDFDF; padding:7px; width:155px; font-family: sans-serif;font-size:12px;text-align:right;"><?php echo esc_html( rgar( $row, 'name' ) ); ?></td>
					<td style="border-bottom:1px solid #DFDFDF; border-right:1px solid #DFDFDF; padding:7px; width:155px; font-family: sans-serif; font-size:11px;">
						<?php echo rgar( $row, 'sub_total_money' ); ?>
					</td>
				</tr>
			<?php } ?>
			<tr>
				<td colspan="2" style="background-color:#F4F4F4; border-bottom:1px solid #DFDFDF; border-right:1px solid #DFDFDF; padding:7px; font-size:11px;">&nbsp;</td>
				<td style="border-bottom:1px solid #DFDFDF; border-right:1px solid #DFDFDF; padding:7px; text-align:right; width:155px; font-family: sans-serif;">
					<strong style="font-size:12px;"><?php esc_html_e( 'Total', 'gravityforms' ); ?></strong></td>
				<td style="border-bottom:1px solid #DFDFDF; border-right:1px solid #DFDFDF; padding:7px; width:155px; font-family: sans-serif;">
					<strong style="font-size:12px;"><?php echo $order_summary['totals']['total_money']; ?></strong></td>
			</tr>
			</tfoot>
		</table>
	</td>
</tr>
