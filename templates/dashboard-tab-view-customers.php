<?php

$atts = array(
    'columns'          => 4,
    'orderby'          => 'meta_key',
    'orderby_meta_key' => '_order_count',
    'order'            => 'asc',
    'role'             => 'customer'
);

// set base query arguments
$query_args = array(
    'fields'  => 'ID',
    'role'    => $atts['role'],
    'order'   => $atts['order'],
    'meta_query' => array(
        array(
            'key'       => '_affiliate_host_id',
            'value'     => affwp_get_affiliate_id(),
            'compare'   => '=',
            'type'      => 'NUMERIC',
        ),
    )
);

// orderby
if ( ! empty( $atts['orderby'] ) ) {
    $query_args['orderby'] = $atts['orderby'];

    
}

$query = new WP_User_Query( $query_args );

$results = $query->get_results();
$html = '';
if( ! empty( $results ) ) {
    $html = '<ul class="products customers top-customers">';

    $loop = 1;
    foreach ( $results as $user_id ) {

        $customer = new WP_User( $user_id );

        $customer_data = array(
            'id'           => $customer->ID,
            'first_name'   => $customer->first_name,
            'last_name'    => $customer->last_name,
            'username'     => $customer->user_login,
            'display_name' => ( $customer->first_name && $customer->last_name ) ? $customer->first_name . ' ' . $customer->last_name : $customer->display_name,
            'orders_count' => (int) $customer->_order_count,
            'total_spent'  => wc_format_decimal( $customer->_money_spent, 2 ),
            'avatar'       => get_avatar( $customer->customer_email ),
        );

        $classes = array();

        if ( 0 == ( $loop - 1 ) % $atts['columns'] || 1 == $atts['columns'] )
            $classes[] = 'first';
        if ( 0 == $loop % $atts['columns'] )
            $classes[] = 'last';

        $classes[] = 'product';
        $classes[] = 'customer';

        $classes = apply_filters( 'wc_top_customers_li_classes', $classes );

        $html .= '<li class="' . implode( ' ', $classes ) . '">';
            $html .= get_avatar( $customer->customer_email );
            $html .= '<h3>' . apply_filters( 'wc_top_customers_display_name', $customer_data['display_name'], $customer_data ) . '</h3>';
            $html .= '<p>' . sprintf( _n( '<strong>%d</strong> order worth <span class="amount price">%s</span>', '<strong>%d</strong> orders worth <span class="amount price">%s</span>', (int) $customer->_order_count, 'wc-top-customers' ), (int) $customer->_order_count, wc_price( $customer->_money_spent ) ) . '</p>';
        $html .= '</li>';

        $loop++;
    }

    $html .= '</ul>';
} else {
    echo '<p>Ups, it seems like you do not have any affiliated customers, please use the "Add Customer" link to create accounts.</p>';
}

echo $html;
