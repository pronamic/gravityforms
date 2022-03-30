<tr>
	<td colspan="2" class="entry-view-field-name"><?php echo esc_html( $order_summary['labels']['order_label'] ); ?></td>
</tr>
<tr>
	<td colspan="2" class="entry-view-field-value lastrow">
		<table class="entry-products" cellspacing="0" width="97%">
			<colgroup>
				<col class="entry-products-col1" />
				<col class="entry-products-col2" />
				<col class="entry-products-col3" />
				<col class="entry-products-col4" />
			</colgroup>
			<thead>
			<th scope="col"><?php echo esc_html( $order_summary['labels']['product'] ); ?></th>
			<th scope="col" class="textcenter"><?php echo esc_html( $order_summary['labels']['product_qty'] ); ?></th>
			<th scope="col"><?php echo esc_html( $order_summary['labels']['product_unitprice'] ); ?></th>
			<th scope="col"><?php echo esc_html( $order_summary['labels']['product_price'] ); ?></th>
			</thead>
			<tbody>
			<?php
			foreach ( rgars( $order_summary, 'rows/body', array() ) as $row ) {
				?>
				<tr>
					<td>
						<div class="product_name"><?php echo esc_html( rgar( $row, 'name' ) ); ?></div>
						<ul class="product_options">
							<?php
							if ( is_array( rgar( $row, 'options' ) ) ) {
								$count = sizeof( $row['options'] );
								for ( $i = 0; $i < $count; $i++ ) {
									?>
										<li <?php echo ( $i === ( $count - 1 ) ? "class='lastitem'" : '' ); ?>><?php echo rgar( $row['options'][ $i ], 'option_label' );?></li>
									<?php
								}
							}
							?>
						</ul>
					</td>
					<td class="textcenter"><?php echo esc_html( rgar( $row, 'quantity' ) ); ?></td>
					<td><?php echo rgar( $row, 'price_money' ); ?></td>
					<td><?php echo rgar( $row, 'sub_total_money' ); ?></td>
				</tr>
			<?php } ?>
			</tbody>
			<tfoot>
				<tr>
					<td colspan="2" class="emptycell">&nbsp;</td>
					<td class="subtotal"><?php esc_html_e( 'Sub Total', 'gravityforms' ); ?></td>
					<td class="subtotal_amount"><?php echo $order_summary['totals']['sub_total_money']; ?></td>
				</tr>
			<?php foreach ( rgars( $order_summary, 'rows/footer', array() ) as $row ) { ?>
				<tr>
					<td colspan="2" class="emptycell">&nbsp;</td>
					<td class="footer_row"><?php echo esc_html( rgar( $row, 'name' ) ); ?></td>
					<td class="footer_row_amount"><?php echo rgar( $row, 'price_money' ); ?>&nbsp;</td>
				</tr>
			<?php } ?>
				<tr>
					<td colspan="2" class="emptycell">&nbsp;</td>
					<td class="grandtotal"><?php esc_html_e( 'Total', 'gravityforms' ); ?></td>
					<td class="grandtotal_amount"><?php echo $order_summary['totals']['total_money']; ?></td>
				</tr>
			</tfoot>
		</table>
	</td>
</tr>

