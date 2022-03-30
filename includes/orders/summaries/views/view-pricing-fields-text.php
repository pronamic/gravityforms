<?php echo "--------------------------------\n";
echo esc_html( $order_summary['labels']['order_label'] ) . "\n\n";
foreach ( rgars( $order_summary, 'rows/body', array() ) as $row ) {

	if ( ! empty( $row['options'] ) ) {
		$row['name'] .= ' (' . implode(
			', ',
			array_map(
				function( $option ) {
					return rgar( $option, 'option_name', '' );
				},
				rgar( $row, 'options', array() )
			)
		) . ')';
	}
	echo rgar( $row, 'quantity' ) . ' ' . rgar( $row, 'name' ) . ': ' . rgar( $row, 'sub_total_money', 0 ) . "\n\n";
}

foreach ( rgars( $order_summary, 'rows/footer', array() ) as $row ) {
	echo rgar( $row, 'name' ) . ': ' . rgar( $row, 'sub_total_money', 0 ) . "\n\n";
}

echo esc_html__( 'Sub Total', 'gravityforms' ) . ': ' . $order_summary['totals']['sub_total_money'] . "\n\n";

echo esc_html__( 'Total', 'gravityforms' ) . ': ' . $order_summary['totals']['total_money'] . "\n\n";
