<?php

/**
 * Class Thim_Service
 *
 * @since 0.8.3
 */
class Thim_Service extends Thim_Admin_Sub_Page {
    /**
     * @var string
     *
     * @since 0.8.5
     */
    public $key_page = 'service';

    /**
     * Thim_Service constructor.
     *
     * @since 0.8.3
     */
    protected function __construct() {
        parent::__construct();

        $this->init_hooks();
    }

    /**
     * Initialize hooks.
     *
     * @since 0.8.3
     */
    private function init_hooks() {
         add_filter( 'thim_dashboard_sub_pages', array( $this, 'add_sub_page' ) );
     }

    /**
     * Add sub page.
     *
     * @since 0.8.5
     *
     * @param $sub_pages
     *
     * @return mixed
     */
    public function add_sub_page( $sub_pages ) {
        $sub_pages['service'] = array(
            'title' => __( 'Service', 'thim-core' ),
            'icon' => '<svg width="26" height="25" viewBox="0 0 26 25" fill="none" xmlns="http://www.w3.org/2000/svg">
						<path d="M23.1562 19.5312H21.5938V17.9688H22.375V14.8438H19.25V15.625H17.6875V14.0625C17.6875 13.8553 17.7698 13.6566 17.9163 13.5101C18.0628 13.3636 18.2615 13.2812 18.4688 13.2812H23.1562C23.3635 13.2812 23.5622 13.3636 23.7087 13.5101C23.8552 13.6566 23.9375 13.8553 23.9375 14.0625V18.75C23.9375 18.9572 23.8552 19.1559 23.7087 19.3024C23.5622 19.4489 23.3635 19.5312 23.1562 19.5312Z" fill="#545454"/>
						<path d="M19.25 23.4375H14.5625C14.3553 23.4375 14.1566 23.3552 14.0101 23.2087C13.8636 23.0622 13.7812 22.8635 13.7812 22.6563V17.9688C13.7812 17.7616 13.8636 17.5628 14.0101 17.4163C14.1566 17.2698 14.3553 17.1875 14.5625 17.1875H19.25C19.4572 17.1875 19.6559 17.2698 19.8024 17.4163C19.9489 17.5628 20.0312 17.7616 20.0312 17.9688V22.6563C20.0312 22.8635 19.9489 23.0622 19.8024 23.2087C19.6559 23.3552 19.4572 23.4375 19.25 23.4375ZM15.3437 21.875H18.4687V18.75H15.3437V21.875ZM12.2187 15.5141C11.6542 15.367 11.1423 15.0645 10.7411 14.6411C10.3398 14.2176 10.0654 13.6901 9.94882 13.1185C9.83228 12.5469 9.87836 11.9541 10.0818 11.4074C10.2853 10.8607 10.6379 10.3819 11.0998 10.0256C11.5616 9.66922 12.1142 9.4495 12.6946 9.39137C13.2751 9.33324 13.8602 9.43902 14.3835 9.69672C14.9069 9.95442 15.3475 10.3537 15.6553 10.8492C15.9631 11.3447 16.1259 11.9167 16.125 12.5H17.6875C17.6884 11.6061 17.4337 10.7306 16.9534 9.97667C16.4731 9.22276 15.7873 8.6219 14.9767 8.24494C14.1662 7.86798 13.2648 7.73064 12.3788 7.84911C11.4927 7.96758 10.6591 8.33692 9.97605 8.91359C9.29302 9.49025 8.78913 10.2502 8.52378 11.1038C8.25843 11.9574 8.24269 12.8691 8.47842 13.7314C8.71414 14.5936 9.1915 15.3705 9.85421 15.9704C10.5169 16.5703 11.3373 16.9682 12.2187 17.1172V15.5141Z" fill="#545454"/>
						<path d="M23.0705 10.5859L21.2658 12.1719L20.1565 11.0625L22.0393 9.40625L20.1955 6.21875L17.508 7.125C16.8785 6.60099 16.1655 6.18637 15.3986 5.89844L14.844 3.125H11.1565L10.6018 5.89844C9.8288 6.17832 9.11187 6.59367 8.48458 7.125L5.80489 6.21875L3.96114 9.40625L6.08614 11.2734C5.94161 12.0821 5.94161 12.9101 6.08614 13.7188L3.96114 15.5937L5.80489 18.7812L8.49239 17.875C9.12193 18.399 9.83496 18.8136 10.6018 19.1016L11.1565 21.875H12.219V23.4375H11.1565C10.7952 23.4372 10.4451 23.3117 10.1659 23.0824C9.88674 22.8531 9.69566 22.5341 9.62521 22.1797L9.22677 20.2109C8.87307 20.0384 8.53117 19.8427 8.20333 19.625L6.30489 20.2656C6.14346 20.3182 5.97466 20.3445 5.80489 20.3438C5.53046 20.3456 5.2605 20.2742 5.0229 20.1369C4.78529 19.9995 4.58867 19.8013 4.45333 19.5625L2.60958 16.375C2.42723 16.0617 2.35947 15.6948 2.4179 15.3371C2.47634 14.9793 2.65733 14.653 2.92989 14.4141L4.42989 13.1016C4.41427 12.8984 4.40646 12.7031 4.40646 12.5C4.40646 12.2969 4.42208 12.1016 4.43771 11.9062L2.92989 10.5859C2.65733 10.347 2.47634 10.0207 2.4179 9.66294C2.35947 9.3052 2.42723 8.93827 2.60958 8.625L4.45333 5.4375C4.58867 5.19875 4.78529 5.00046 5.0229 4.86312C5.2605 4.72578 5.53046 4.65436 5.80489 4.65625C5.97466 4.65547 6.14346 4.68184 6.30489 4.73438L8.19552 5.375C8.52604 5.15727 8.87053 4.96153 9.22677 4.78906L9.62521 2.82031C9.69566 2.46595 9.88674 2.14695 10.1659 1.91761C10.4451 1.68828 10.7952 1.56279 11.1565 1.5625H14.844C15.2053 1.56279 15.5553 1.68828 15.8345 1.91761C16.1137 2.14695 16.3048 2.46595 16.3752 2.82031L16.7736 4.78906C17.1273 4.96157 17.4692 5.15731 17.7971 5.375L19.6955 4.73438C19.857 4.68184 20.0258 4.65547 20.1955 4.65625C20.47 4.65436 20.7399 4.72578 20.9775 4.86312C21.2151 5.00046 21.4117 5.19875 21.5471 5.4375L23.3908 8.625C23.5732 8.93827 23.6409 9.3052 23.5825 9.66294C23.5241 10.0207 23.3431 10.347 23.0705 10.5859Z" fill="#545454"/>
					</svg>',
        );

        return $sub_pages;
    }

}