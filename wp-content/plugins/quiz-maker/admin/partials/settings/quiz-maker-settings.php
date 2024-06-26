<?php
    $actions = $this->settings_obj;

    if( isset( $_REQUEST['ays_submit'] ) ){
        $actions->store_data($_REQUEST);
    }
    if(isset($_GET['ays_quiz_tab'])){
        $ays_quiz_tab = $_GET['ays_quiz_tab'];
    }else{
        $ays_quiz_tab = 'tab1';
    }

    if(isset($_GET['action']) && $_GET['action'] == 'update_duration'){
        $actions->update_duration_data();
    }

    $data = $actions->get_data();
    $db_data = $actions->get_db_data();
    $options = ($actions->ays_get_setting('options') === false) ? array() : json_decode($actions->ays_get_setting('options'), true);

    $paypal_client_id = isset($data['paypal_client_id']) ? $data['paypal_client_id'] : '' ;
    $paypal_payment_terms = isset($data['payment_terms']) ? $data['payment_terms'] : 'lifetime' ;

    global $wp_roles;
    $ays_users_roles = $wp_roles->role_names;
    $user_roles = json_decode($actions->ays_get_setting('user_roles'), true);
    $mailchimp_res = ($actions->ays_get_setting('mailchimp') === false) ? json_encode(array()) : $actions->ays_get_setting('mailchimp');
    $mailchimp = json_decode($mailchimp_res, true);
    $mailchimp_username = isset($mailchimp['username']) ? $mailchimp['username'] : '' ;
    $mailchimp_api_key = isset($mailchimp['apiKey']) ? $mailchimp['apiKey'] : '' ;

    $monitor_res     = ($actions->ays_get_setting('monitor') === false) ? json_encode(array()) : $actions->ays_get_setting('monitor');
    $monitor         = json_decode($monitor_res, true);
    $monitor_client  = isset($monitor['client']) ? $monitor['client'] : '';
    $monitor_api_key = isset($monitor['apiKey']) ? $monitor['apiKey'] : '';

    $zapier_res  = ($actions->ays_get_setting('zapier') === false) ? json_encode(array()) : $actions->ays_get_setting('zapier');
    $zapier      = json_decode($zapier_res, true);
    $zapier_hook = isset($zapier['hook']) ? $zapier['hook'] : '';

    $active_camp_res     = ($actions->ays_get_setting('active_camp') === false) ? json_encode(array()) : $actions->ays_get_setting('active_camp');
    $active_camp         = json_decode($active_camp_res, true);
    $active_camp_url     = isset($active_camp['url']) ? $active_camp['url'] : '';
    $active_camp_api_key = isset($active_camp['apiKey']) ? $active_camp['apiKey'] : '';

    $slack_res    = ($actions->ays_get_setting('slack') === false) ? json_encode(array()) : $actions->ays_get_setting('slack');
    $slack        = json_decode($slack_res, true);
    $slack_client = isset($slack['client']) ? $slack['client'] : '';
    $slack_secret = isset($slack['secret']) ? $slack['secret'] : '';
    $slack_token = isset($slack['token']) ? $slack['token'] : '';
    $slack_oauth  = !empty($_GET['oauth']) && $_GET['oauth'] == 'slack';
    if ($slack_oauth) {
        $slack_temp_code = !empty($_GET['code']) ? $_GET['code'] : "";
        $slack_client    = !empty($_GET['state']) ? $_GET['state'] : "";
        $ays_quiz_tab    = 'tab2';
    }

    // Google sheets Xcho
    $google_res          = ($actions->ays_get_setting('google') === false) ? json_encode(array()) : $actions->ays_get_setting('google');
    $google_sheets       = json_decode($google_res, true);
    $google_client       = isset($google_sheets['client']) ? $google_sheets['client'] : '';
    $google_secret       = isset($google_sheets['secret']) ? $google_sheets['secret'] : '';
    $google_redirect_uri = isset($google_sheets['redirect_uri']) ? $google_sheets['redirect_uri'] : '';
    $google_token        = isset($google_sheets['token']) ? $google_sheets['token'] : '';
    $google_redirect_url = menu_page_url("quiz-maker-settings", false);

    $google_code  = !empty($_GET['code']) ? $_GET['code'] : "";
    $google_scope  = !empty($_GET['scope']) ? $_GET['scope'] : "";
    $google_code_check  = !empty($_GET['code']) && !isset($_GET['oauth']) ? true : false;

    if( $google_code && $google_scope ){
        $ays_quiz_tab = 'tab2';
    }

    if( isset( $_REQUEST['ays_disconnect_google_sheets'] ) ){
        $result = $actions->ays_update_setting('google', '');
        Quiz_Maker_Data::delete_quiz_sheet_ids();

        $url = menu_page_url("quiz-maker-settings", false);
        $url = add_query_arg( array(
            'ays_quiz_tab' => 'tab2',
            'status' => 'gdisconnected'
        ), $url );
        wp_redirect( $url );
        exit();
    }

    if( isset( $_REQUEST['ays_googleOAuth2'] ) ){

        // Google sheets
        $gclient_id = isset($_REQUEST['ays_google_client']) && $_REQUEST['ays_google_client'] != '' ? $_REQUEST['ays_google_client'] : '';
        $gclient_secret = isset($_REQUEST['ays_google_secret']) && $_REQUEST['ays_google_secret'] != '' ? $_REQUEST['ays_google_secret'] : '';
        $gredirect_url = isset($_REQUEST['ays_google_redirect']) && $_REQUEST['ays_google_redirect'] != '' ? $_REQUEST['ays_google_redirect'] : '';
        $google_sheets = array(
            'client' => $gclient_id,
            'secret' => $gclient_secret,
            'redirect_uri' => $gredirect_url,
        );
        $result = $actions->ays_update_setting('google', json_encode($google_sheets));

        $scopes = array(
            'https://www.googleapis.com/auth/spreadsheets',
            'https://www.googleapis.com/auth/userinfo.profile',
            'https://www.googleapis.com/auth/userinfo.email',
        );
        $glogin_url = 'https://accounts.google.com/o/oauth2/v2/auth?scope=' .
            urlencode( implode( ' ', $scopes ) ) .
            '&redirect_uri=' . urlencode( $gredirect_url ) . '&response_type=code&client_id=' . $gclient_id . '&access_type=offline&prompt=consent';

        wp_redirect( $glogin_url );
        exit();
    }

    $gerror_message = '';
    // Google passes a parameter 'code' in the Redirect Url
    if( $google_code && $google_scope ) {
        try {
            // Get the access token
            $gtokens = Quiz_Maker_Data::GetGoogleUserToken_RefreshToken($google_client, $google_redirect_url, $google_secret, $_GET['code']);

            // Access Token
            $gaccess_token = $gtokens['access_token'];

            // Get user information
            $google_user_info = Quiz_Maker_Data::GetGoogleUserProfileInfo( $gaccess_token );

            $google_sheets = array(
                'client' => $google_client,
                'secret' => $google_secret,
                'redirect_uri' => $google_redirect_uri,
                'token' => $gaccess_token,
                'refresh_token' => $gtokens['refresh_token'],
                'user_email' => $google_user_info['email'],
                'user_name' => $google_user_info['name'],
                'user_picture' => $google_user_info['picture'],
                'user_gid' => $google_user_info['id'],
            );

            $result = $actions->ays_update_setting('google', json_encode($google_sheets));
            $url = menu_page_url("quiz-maker-settings", false);
            $url = add_query_arg( array(
                'ays_quiz_tab' => 'tab2',
                'status' => 'gconnected'
            ), $url );
            wp_redirect( $url );
            exit();
        } catch(Exception $e) {
            $gerror_message = $e->getMessage();
        }
    }

    $google_res     = ($actions->ays_get_setting('google') === false) ? json_encode(array()) : $actions->ays_get_setting('google');
    $google_sheets  = json_decode($google_res, true);
    $google_email   = isset($google_sheets['user_email']) ? $google_sheets['user_email'] : '';
    $google_name    = isset($google_sheets['user_name']) ? $google_sheets['user_name'] : '';
    $google_picture = isset($google_sheets['user_picture']) ? $google_sheets['user_picture'] : '';
    $google_token   = isset($google_sheets['token']) ? $google_sheets['token'] : '';


    // AV Leaderboard

    $leadboard_res = ($actions->ays_get_setting('leaderboard') === false) ? json_encode(array()) : $actions->ays_get_setting('leaderboard');
    $leadboard = json_decode($leadboard_res, true);

    $ind_leadboard_count = isset($leadboard['individual']['count']) ? $leadboard['individual']['count'] : '5' ;
    $ind_leadboard_width = isset($leadboard['individual']['width']) ? $leadboard['individual']['width'] : '0' ;
    $ind_leadboard_orderby = isset($leadboard['individual']['orderby']) ? $leadboard['individual']['orderby'] : 'id' ;
    $ind_leadboard_sort = isset($leadboard['individual']['sort']) ? $leadboard['individual']['sort'] : 'avg' ;
    $ind_leadboard_color = isset($leadboard['individual']['color']) ? $leadboard['individual']['color'] : '#99BB5A' ;
    $ind_leadboard_suctom_css = (isset($leadboard['individual']['leadboard_custom_css']) && $leadboard['individual']['leadboard_custom_css'] != '') ? $leadboard['individual']['leadboard_custom_css'] : '';

    $glob_leadboard_count = isset($leadboard['global']['count']) ? $leadboard['global']['count'] : '5' ;
    $glob_leadboard_width = isset($leadboard['global']['width']) ? $leadboard['global']['width'] : '0' ;
    $glob_leadboard_orderby = isset($leadboard['global']['orderby']) ? $leadboard['global']['orderby'] : 'id' ;
    $glob_leadboard_sort = isset($leadboard['global']['sort']) ? $leadboard['global']['sort'] : 'avg' ;
    $glob_leadboard_color = isset($leadboard['global']['color']) ? $leadboard['global']['color'] : '#99BB5A' ;
    $glob_leadboard_suctom_css = (isset($leadboard['global']['gleadboard_custom_css']) && $leadboard['global']['gleadboard_custom_css'] != '') ? $leadboard['global']['gleadboard_custom_css'] : '';

    //AV end

    $default_leadboard_columns = array(
        'pos' => 'pos',
        'name' => 'name',
        'score' => 'score',
        'duration' => 'duration',
        'points' => '',
    );

    $default_leadboard_column_names = array(
        "pos" => __( 'Pos.', $this->plugin_name ),
        "name" => __( 'Name', $this->plugin_name ),
        "score" => __( 'Score', $this->plugin_name ),
        "duration" => __( 'Duration', $this->plugin_name ),
        "points" => __( 'Points', $this->plugin_name ),
    );

    // Individual Leaderboard
    $leadboard['individual']['ind_leadboard_columns'] = ! isset( $leadboard['individual']['ind_leadboard_columns'] ) ? $default_leadboard_columns : $leadboard['individual']['ind_leadboard_columns'];
    $ind_leadboard_columns = (isset( $leadboard['individual']['ind_leadboard_columns'] ) && !empty($leadboard['individual']['ind_leadboard_columns']) ) ? $leadboard['individual']['ind_leadboard_columns'] : array();
    $ind_leadboard_columns_order = (isset( $leadboard['individual']['ind_leadboard_columns_order'] ) && !empty($leadboard['individual']['ind_leadboard_columns_order']) ) ? $leadboard['individual']['ind_leadboard_columns_order'] : $default_leadboard_columns;

    // Global Leaderboard
    $leadboard['global']['glob_leadboard_columns'] = ! isset( $leadboard['global']['glob_leadboard_columns'] ) ? $default_leadboard_columns : $leadboard['global']['glob_leadboard_columns'];
    $glob_leadboard_columns = (isset( $leadboard['global']['glob_leadboard_columns'] ) && !empty($leadboard['global']['glob_leadboard_columns']) ) ? $leadboard['global']['glob_leadboard_columns'] : array();
    $glob_leadboard_columns_order = (isset( $leadboard['global']['glob_leadboard_columns_order'] ) && !empty($leadboard['global']['glob_leadboard_columns_order']) ) ? $leadboard['global']['glob_leadboard_columns_order'] : $default_leadboard_columns;

    $quizzes = $actions->get_reports_titles();
    $empry_dur_count = $actions->get_empty_duration_rows_count();

    $question_types = array(
        "radio" => __("Radio", $this->plugin_name),
        "checkbox" => __("Checkbox( Multiple )", $this->plugin_name),
        "select" => __("Dropdown", $this->plugin_name),
        "text" => __("Text", $this->plugin_name),
        "short_text" => __("Short Text", $this->plugin_name),
        "number" => __("Number", $this->plugin_name),
        "date" => __("Date", $this->plugin_name),
        "custom" => __("Custom (Banner)", $this->plugin_name),
    );

    $options['question_default_type'] = !isset($options['question_default_type']) ? 'radio' : $options['question_default_type'];
    $question_default_type = isset($options['question_default_type']) ? $options['question_default_type'] : '';

    // Default Category
    $question_default_cat = isset($options['question_default_cat']) && $options['question_default_cat'] != '' ? absint(intval($options['question_default_cat'])) : 0;
    $question_categories = Quiz_Maker_Data::get_question_categories();


    $options['ays_show_result_report'] = !isset( $options['ays_show_result_report'] ) ? 'on' : $options['ays_show_result_report'];
//    $show_result_report = ( isset( $options['ays_show_result_report'] ) && $options['ays_show_result_report'] == 'on' ) ? 'on' : 'off';
//    $show_result_report = ( isset( $options['ays_show_result_report'] ) && $options['ays_show_result_report'] != 'on' ) ? 'off' : 'on';
    $ays_answer_default_count = isset($options['ays_answer_default_count']) ? $options['ays_answer_default_count'] : '3';
    $right_answer_sound = isset($options['right_answer_sound']) ? $options['right_answer_sound'] : '';
    $wrong_answer_sound = isset($options['wrong_answer_sound']) ? $options['wrong_answer_sound'] : '';

    $default_user_page_columns = array(
        'quiz_name' => 'quiz_name',
        'start_date' => 'start_date',
        'end_date' => 'end_date',
        'duration' => 'duration',
        'score' => 'score',
        'details' => 'details',
    );

    $options['user_page_columns'] = ! isset( $options['user_page_columns'] ) ? $default_user_page_columns : $options['user_page_columns'];
    $user_page_columns = (isset( $options['user_page_columns'] ) && !empty($options['user_page_columns']) ) ? $options['user_page_columns'] : array();
    $user_page_columns_order = (isset( $options['user_page_columns_order'] ) && !empty($options['user_page_columns_order']) ) ? $options['user_page_columns_order'] : $default_user_page_columns;

    $default_user_page_column_names = array(
        "quiz_name" => __( 'Quiz name', $this->plugin_name ),
        "start_date" => __( 'Start date', $this->plugin_name ),
        "end_date" => __( 'End date', $this->plugin_name ),
        "duration" => __( 'Duration', $this->plugin_name ),
        "score" => __( 'Score', $this->plugin_name ),
        "details" => __( 'Details', $this->plugin_name )
    );

    // Aro Buttons Text

    $buttons_texts_res      = ($actions->ays_get_setting('buttons_texts') === false) ? json_encode(array()) : $actions->ays_get_setting('buttons_texts');
    $buttons_texts          = json_decode($buttons_texts_res, true);

    $start_button           = (isset($buttons_texts['start_button']) && $buttons_texts['start_button'] != '') ? $buttons_texts['start_button'] : 'Start' ;
    $next_button            = (isset($buttons_texts['next_button']) && $buttons_texts['next_button'] != '') ? $buttons_texts['next_button'] : 'Next' ;
    $previous_button        = (isset($buttons_texts['previous_button']) && $buttons_texts['previous_button'] != '') ? $buttons_texts['previous_button'] : 'Prev' ;
    $clear_button           = (isset($buttons_texts['clear_button']) && $buttons_texts['clear_button'] != '') ? $buttons_texts['clear_button'] : 'Clear' ;
    $finish_button          = (isset($buttons_texts['finish_button']) && $buttons_texts['finish_button'] != '') ? $buttons_texts['finish_button'] : 'Finish' ;
    $see_result_button      = (isset($buttons_texts['see_result_button']) && $buttons_texts['see_result_button'] != '') ? $buttons_texts['see_result_button'] : 'See Result' ;
    $restart_quiz_button    = (isset($buttons_texts['restart_quiz_button']) && $buttons_texts['restart_quiz_button'] != '') ? $buttons_texts['restart_quiz_button'] : 'Restart quiz' ;
    $send_feedback_button   = (isset($buttons_texts['send_feedback_button']) && $buttons_texts['send_feedback_button'] != '') ? $buttons_texts['send_feedback_button'] : 'Send feedback' ;
    $load_more_button       = (isset($buttons_texts['load_more_button']) && $buttons_texts['load_more_button'] != '') ? $buttons_texts['load_more_button'] : 'Load more' ;
    $exit_button            = (isset($buttons_texts['exit_button']) && $buttons_texts['exit_button'] != '') ? $buttons_texts['exit_button'] : 'Exit' ;
    $check_button           = (isset($buttons_texts['check_button']) && $buttons_texts['check_button'] != '') ? $buttons_texts['check_button'] : 'Check' ;
    $login_button           = (isset($buttons_texts['login_button']) && $buttons_texts['login_button'] != '') ? $buttons_texts['login_button'] : 'Log In' ;

    //Aro end

    //Questions title length
    $question_title_length = (isset($options['question_title_length']) && intval($options['question_title_length']) != 0) ? absint(intval($options['question_title_length'])) : 5;
    if($question_title_length == 0){
        $question_title_length = 5;
    }

    //Quizzes title length
    $quizzes_title_length = (isset($options['quizzes_title_length']) && intval($options['quizzes_title_length']) != 0) ? absint(intval($options['quizzes_title_length'])) : 5;
    if($quizzes_title_length == 0){
        $quizzes_title_length = 5;
    }

    //Results title length
    $results_title_length = (isset($options['results_title_length']) && intval($options['results_title_length']) != 0) ? absint(intval($options['results_title_length'])) : 5;
    if($results_title_length == 0){
        $results_title_length = 5;
    }


    // Do not store IP adressess
    $options['disable_user_ip'] = isset($options['disable_user_ip']) ? $options['disable_user_ip'] : 'off';
    $disable_user_ip = (isset($options['disable_user_ip']) && $options['disable_user_ip'] == "on") ? true : false;

    //default all results column
    $default_all_results_columns = array(
        'user_name'    => 'user_name',
        'quiz_name'    => 'quiz_name',
        'start_date'   => 'start_date',
        'end_date'     => 'end_date',
        'duration'     => 'duration',
        'score'        => 'score',
        // 'details' => 'details',
    );
    $options['all_results_columns'] = ! isset( $options['all_results_columns'] ) ? $default_all_results_columns : $options['all_results_columns'];
    $all_results_columns = (isset( $options['all_results_columns'] ) && !empty($options['all_results_columns']) ) ? $options['all_results_columns'] : array();
    $all_results_columns_order = (isset( $options['all_results_columns_order'] ) && !empty($options['all_results_columns_order']) ) ? $options['all_results_columns_order'] : $default_all_results_columns;

    $default_all_results_column_names = array(
        "user_name"  => __( 'User name', $this->plugin_name),
        "quiz_name"  => __( 'Quiz name', $this->plugin_name ),
        "start_date" => __( 'Start date',$this->plugin_name ),
        "end_date"   => __( 'End date',  $this->plugin_name ),
        "duration"   => __( 'Duration',  $this->plugin_name ),
        "score"      => __( 'Score',     $this->plugin_name ),
        // "details" => __( 'Details', $this->plugin_name )
    );

    // Show publicly
    $options['all_results_show_publicly'] = isset($options['all_results_show_publicly']) ? $options['all_results_show_publicly'] : 'off';
    $all_results_show_publicly = (isset($options['all_results_show_publicly']) && $options['all_results_show_publicly'] == "on") ? true : false;

?>
<div class="wrap" style="position:relative;">
    <div class="container-fluid">
        <form method="post" id="ays-quiz-settings-form">
            <input type="hidden" name="ays_quiz_tab" value="<?php echo $ays_quiz_tab; ?>">
            <h1 class="wp-heading-inline">
            <?php
                echo __('General Settings',$this->plugin_name);
            ?>
            </h1>
            <?php                
                if( isset( $_REQUEST['status'] ) ){
                    $actions->quiz_settings_notices($_REQUEST['status']);
                }
            ?>
            <hr/>
            <div class="ays-settings-wrapper">
            <div>
                <div class="nav-tab-wrapper" style="position:sticky; top:35px;">
                    <a href="#tab1" data-tab="tab1" class="nav-tab <?php echo ($ays_quiz_tab == 'tab1') ? 'nav-tab-active' : ''; ?>">
                        <?php echo __("General", $this->plugin_name);?>
                    </a>
                    <a href="#tab2" data-tab="tab2" class="nav-tab <?php echo ($ays_quiz_tab == 'tab2') ? 'nav-tab-active' : ''; ?>">
                        <?php echo __("Integrations", $this->plugin_name);?>
                    </a>
                    <a href="#tab3" data-tab="tab3" class="nav-tab <?php echo ($ays_quiz_tab == 'tab3') ? 'nav-tab-active' : ''; ?>">
                        <?php echo __("Shortcodes", $this->plugin_name);?>
                    </a>
                    <a href="#tab4" data-tab="tab4" class="nav-tab <?php echo ($ays_quiz_tab == 'tab4') ? 'nav-tab-active' : ''; ?>">
                        <?php echo __("Message variables", $this->plugin_name);?>
                    </a>
                    <a href="#tab5" data-tab="tab5" class="nav-tab <?php echo ($ays_quiz_tab == 'tab5') ? 'nav-tab-active' : ''; ?>">
                        <?php echo __("Buttons Texts", $this->plugin_name);?>
                    </a>
                </div>
            </div>
            <div class="ays-quiz-tabs-wrapper">
                <div id="tab1" class="ays-quiz-tab-content <?php echo ($ays_quiz_tab == 'tab1') ? 'ays-quiz-tab-content-active' : ''; ?>">
                    <p class="ays-subtitle"><?php echo __('General Settings',$this->plugin_name)?></p>
                    <hr/>
                    <fieldset>
                        <legend>
                            <strong style="font-size:30px;"><i class="ays_fa ays_fa_globe"></i></strong>
                            <h5><?php echo __('Who will have permission to Quiz menu',$this->plugin_name)?></h5>
                        </legend>
                        <div class="form-group row">
                            <div class="col-sm-4">
                                <label for="ays_user_roles">
                                    <?php echo __( "Select user role", $this->plugin_name ); ?>
                                    <a class="ays_help" data-toggle="tooltip" title="<?php echo __('Ability to manage Quiz Maker plugin only for selected user roles.',$this->plugin_name)?>">
                                        <i class="ays_fa ays_fa_info_circle"></i>
                                    </a>
                                </label>
                            </div>
                            <div class="col-sm-8">
                                <select name="ays_user_roles[]" id="ays_user_roles" multiple>
                                    <?php
                                        foreach($ays_users_roles as $role => $role_name){
                                            $selected = in_array($role, $user_roles) ? 'selected' : '';
                                            echo "<option ".$selected." value='".$role."'>".$role_name."</option>";
                                        }
                                    ?>
                                </select>
                            </div>
                        </div>
                        <blockquote>
                            <?php echo __( "Ability to manage Quiz Maker plugin only for selected user roles.", $this->plugin_name ); ?>
                        </blockquote>
                    </fieldset>
                    <hr>
                    <fieldset>
                        <legend>
                            <strong style="font-size:30px;"><i class="ays_fa ays_fa_question_circle"></i></strong>
                            <h5><?php echo __('Default parameters for Quiz',$this->plugin_name)?></h5>
                        </legend>
                        <div class="form-group row">
                            <div class="col-sm-4">
                                <label for="ays_questions_default_type">
                                    <?php echo __( "Questions default type", $this->plugin_name ); ?>
                                    <a class="ays_help" data-toggle="tooltip" title="<?php echo __('You can choose default question type which will be selected in the Add new question page.',$this->plugin_name)?>">
                                        <i class="ays_fa ays_fa_info_circle"></i>
                                    </a>
                                </label>
                            </div>
                            <div class="col-sm-8">
                                <select id="ays-type" name="ays_question_default_type">
                                    <option></option>
                                    <?php
                                        foreach($question_types as $type => $label):
                                        $selected = $question_default_type == $type ? "selected" : "";
                                    ?>
                                    <option value="<?php echo $type; ?>" <?php echo $selected; ?> ><?php echo $label; ?></option>
                                    <?php
                                        endforeach;
                                    ?>
                                </select>
                            </div>
                        </div>
                        <div class="form-group row">
                            <div class="col-sm-4">
                                <label for="ays_answer_default_count">
                                    <?php echo __( "Answer default count", $this->plugin_name ); ?>
                                    <a class="ays_help" data-toggle="tooltip" title="<?php echo __('You can write the default answer count which will be showing in the Add new question page (this will work only with radio, checkbox, and dropdown types).',$this->plugin_name)?>">
                                        <i class="ays_fa ays_fa_info_circle"></i>
                                    </a>
                                </label>
                            </div>
                            <div class="col-sm-8">
                                <input type="number" name="ays_answer_default_count" id="ays_answer_default_count" min="2" class="ays-text-input" value="<?php echo $ays_answer_default_count; ?>">
                            </div>
                        </div>
                        <div class="form-group row">
                            <div class="col-sm-4">
                                <label for="ays_questions_default_cat">
                                    <?php echo __( "Questions default category", $this->plugin_name ); ?>
                                    <a class="ays_help" data-toggle="tooltip" title="<?php echo __('You can choose default question category which will be selected in the Add new question page.',$this->plugin_name)?>">
                                        <i class="ays_fa ays_fa_info_circle"></i>
                                    </a>
                                </label>
                            </div>
                            <div class="col-sm-8">
                                <select id="ays-cat" name="ays_questions_default_cat">
                                     <option></option>
                                     <?php
                                        $cat = 0;
                                        foreach ($question_categories as $question_category) {
                                            $checked = (intval($question_category['id']) == $question_default_cat) ? "selected" : "";
                                            if ($cat == 0 && $question_default_cat == 0) {
                                                $checked = 'selected';
                                            }
                                            echo "<option value='" . $question_category['id'] . "' " . $checked . ">" . stripslashes($question_category['title']) . "</option>";
                                            $cat++;
                                        }
                                    ?>
                                </select>
                            </div>
                        </div>
                    </fieldset>
                    <hr>
                    <fieldset>
                        <legend>
                            <strong style="font-size:30px;"><i class="ays_fa ays_fa_user_ip"></i></strong>
                            <h5><?php echo __('Users IP adressess',$this->plugin_name)?></h5>
                        </legend>
                        <blockquote class="ays_warning">
                            <p style="margin:0;"><?php echo __( "If this option is enabled then the 'Limitation by IP' option will not work!", $this->plugin_name ); ?></p>
                        </blockquote>
                        <hr/>
                        <div class="form-group row">
                            <div class="col-sm-4">
                                <label for="ays_disable_user_ip">
                                    <?php echo __( "Do not store IP adressess", $this->plugin_name ); ?>
                                    <a class="ays_help" data-toggle="tooltip" title="<?php echo __('After enabling this option, IP address of the users will not be stored in database. Note: If this option is enabled, then the `Limits user by IP` option will not work.',$this->plugin_name)?>">
                                        <i class="ays_fa ays_fa_info_circle"></i>
                                    </a>
                                </label>
                            </div>
                            <div class="col-sm-8">
                                <input type="checkbox" class="ays-checkbox-input" id="ays_disable_user_ip" name="ays_disable_user_ip" value="on" <?php echo $disable_user_ip ? 'checked' : ''; ?> />
                            </div>
                        </div>
                    </fieldset>
                    <hr>
                    <fieldset>
                        <legend>
                            <strong style="font-size:30px;"><i class="ays_fa ays_fa_music"></i></strong>
                            <h5><?php echo __('Quiz Right/Wrong answers sounds',$this->plugin_name)?></h5>
                        </legend>
                        <div class="form-group row">
                            <div class="col-sm-4">
                                <label for="ays_questions_default_type">
                                    <?php echo __( "Sounds for right/wrong answers", $this->plugin_name ); ?>
                                    <a class="ays_help" data-toggle="tooltip" title="<?php echo __('This option will work with Enable correct answers option.',$this->plugin_name); ?>">
                                        <i class="ays_fa ays_fa_info_circle"></i>
                                    </a>
                                </label>
                            </div>
                            <div class="col-sm-8">
                                <div class="form-group row">
                                    <div class="col-sm-12">
                                        <label for="ays_questions_default_type">
                                            <?php echo __( "Sounds for right answers", $this->plugin_name ); ?>
                                        </label>
                                        <div class="ays-bg-music-container">
                                            <a class="add-quiz-bg-music" href="javascript:void(0);"><?php echo __("Select sound", $this->plugin_name); ?></a>
                                            <audio controls src="<?php echo $right_answer_sound; ?>"></audio>
                                            <input type="hidden" name="ays_right_answer_sound" class="ays_quiz_bg_music" value="<?php echo $right_answer_sound; ?>">
                                        </div>
                                    </div>
                                </div>
                                <hr>
                                <div class="form-group row">
                                    <div class="col-sm-12">
                                        <label for="ays_questions_default_type">
                                            <?php echo __( "Sounds for wrong answers", $this->plugin_name ); ?>
                                        </label>
                                        <div class="ays-bg-music-container">
                                            <a class="add-quiz-bg-music" href="javascript:void(0);"><?php echo __("Select sound", $this->plugin_name); ?></a>
                                            <audio controls src="<?php echo $wrong_answer_sound; ?>"></audio>
                                            <input type="hidden" name="ays_wrong_answer_sound" class="ays_quiz_bg_music" value="<?php echo $wrong_answer_sound; ?>">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </fieldset>
                    <hr>
                    <fieldset>
                        <legend>
                            <strong style="font-size:30px;"><i class="ays_fa ays_fa_text"></i></strong>
                            <h5><?php echo __('Excerpt words count in list tables',$this->plugin_name)?></h5>
                        </legend>
                        <div class="form-group row">
                            <div class="col-sm-4">
                                <label for="ays_question_title_length">
                                    <?php echo __( "Questions list table", $this->plugin_name ); ?>
                                    <a class="ays_help" data-toggle="tooltip" title="<?php echo __('Determine the length of the questions to be shown in the Questions List Table by putting your preferred count of words in the following field. (For example: if you put 10,  you will see the first 10 words of each question in the Questions page of your dashboard.',$this->plugin_name)?>">
                                        <i class="ays_fa ays_fa_info_circle"></i>
                                    </a>
                                </label>
                            </div>
                            <div class="col-sm-8">
                                <input type="number" name="ays_question_title_length" id="ays_question_title_length" class="ays-text-input" value="<?php echo $question_title_length; ?>">
                            </div>
                        </div>
                        <div class="form-group row">
                            <div class="col-sm-4">
                                <label for="ays_quizzes_title_length">
                                    <?php echo __( "Quizzes list table", $this->plugin_name ); ?>
                                    <a class="ays_help" data-toggle="tooltip" title="<?php echo __('Determine the length of the quizzes to be shown in the Quizzes List Table by putting your preferred count of words in the following field. (For example: if you put 10,  you will see the first 10 words of each quiz in the Quizzes page of your dashboard.',$this->plugin_name)?>">
                                        <i class="ays_fa ays_fa_info_circle"></i>
                                    </a>
                                </label>
                            </div>
                            <div class="col-sm-8">
                                <input type="number" name="ays_quizzes_title_length" id="ays_quizzes_title_length" class="ays-text-input" value="<?php echo $quizzes_title_length; ?>">
                            </div>
                        </div>
                        <div class="form-group row">
                            <div class="col-sm-4">
                                <label for="ays_results_title_length">
                                    <?php echo __( "Results list table", $this->plugin_name ); ?>
                                    <a class="ays_help" data-toggle="tooltip" title="<?php echo __('Determine the length of the results to be shown in the Results List Table by putting your preferred count of words in the following field. (For example: if you put 10,  you will see the first 10 words of each result in the Results page of your dashboard.',$this->plugin_name)?>">
                                        <i class="ays_fa ays_fa_info_circle"></i>
                                    </a>
                                </label>
                            </div>
                            <div class="col-sm-8">
                                <input type="number" name="ays_results_title_length" id="ays_results_title_length" class="ays-text-input" value="<?php echo $results_title_length; ?>">
                            </div>
                        </div>
                    </fieldset>
                    <hr>
                    <fieldset>
                        <legend>
                            <strong style="font-size:30px;"><i class="ays_fa ays_fa_trash"></i></strong>
                            <h5><?php echo __('Erase Quiz data',$this->plugin_name)?></h5>
                        </legend>
                        <?php if( isset( $_GET['del_stat'] ) ): ?>
                        <blockquote style="border-color:#46b450;background: rgba(70, 180, 80, 0.2);">
                            <?php echo __("Results up to a ".$_GET['mcount']." month ago deleted successfully.", $this->plugin_name); ?>
                        </blockquote>
                        <hr>
                        <?php endif; ?>
                        <div class="form-group row">
                            <div class="col-sm-4">
                                <label for="ays_delete_results_by">
                                    <?php echo __( "Delete results older then 'X' the month", $this->plugin_name ); ?>
                                    <a class="ays_help" data-toggle="tooltip" title="<?php echo __('Specify count of months and save changes. Attention! it will remove submissions older than specified months permanently.',$this->plugin_name)?>">
                                        <i class="ays_fa ays_fa_info_circle"></i>
                                    </a>
                                </label>
                            </div>
                            <div class="col-sm-8">
                                <input type="number" name="ays_delete_results_by" id="ays_delete_results_by" class="ays-text-input">
                            </div>
                        </div>
                    </fieldset>
                </div>
                <div id="tab2" class="ays-quiz-tab-content <?php echo ($ays_quiz_tab == 'tab2') ? 'ays-quiz-tab-content-active' : ''; ?>">
                    <p class="ays-subtitle"><?php echo __('Integrations',$this->plugin_name)?></p>
                    <hr/>
                    <fieldset>
                        <legend>
                            <img class="ays_integration_logo" src="<?php echo AYS_QUIZ_ADMIN_URL; ?>/images/integrations/mailchimp_logo.png" alt="">
                            <h5><?php echo __('MailChimp',$this->plugin_name)?></h5>
                        </legend>
                        <div class="form-group row">
                            <div class="col-sm-12">
                                <div class="form-group row" aria-describedby="aaa">
                                    <div class="col-sm-3">
                                        <label for="ays_mailchimp_username">
                                            <?php echo __('MailChimp Username',$this->plugin_name)?>
                                        </label>
                                    </div>
                                    <div class="col-sm-9">
                                        <input type="text" 
                                            class="ays-text-input" 
                                            id="ays_mailchimp_username" 
                                            name="ays_mailchimp_username"
                                            value="<?php echo $mailchimp_username; ?>"
                                        />
                                    </div>
                                </div>
                                <hr/>
                                <div class="form-group row" aria-describedby="aaa">
                                    <div class="col-sm-3">
                                        <label for="ays_mailchimp_api_key">
                                            <?php echo __('MailChimp API Key',$this->plugin_name)?>
                                        </label>
                                    </div>
                                    <div class="col-sm-9">
                                        <input type="text" 
                                            class="ays-text-input" 
                                            id="ays_mailchimp_api_key" 
                                            name="ays_mailchimp_api_key"
                                            value="<?php echo $mailchimp_api_key; ?>"
                                        />
                                    </div>
                                </div>
                                <blockquote>
                                    <?php echo sprintf( __( "You can get your API key from your ", $this->plugin_name ) . "<a href='%s' target='_blank'> %s.</a>", "https://us20.admin.mailchimp.com/account/api/", "Account Extras menu" ); ?>
                                </blockquote>
                            </div>
                        </div>
                    </fieldset>
                    <hr/>
                    <fieldset>
                        <legend>
                            <img class="ays_integration_logo" src="<?php echo AYS_QUIZ_ADMIN_URL; ?>/images/integrations/campaignmonitor_logo.png" alt="">
                            <h5><?php echo __('Campaign Monitor',$this->plugin_name)?></h5>
                        </legend>
                        <div class="form-group row">
                            <div class="col-sm-12">
                                <div class="form-group row" aria-describedby="aaa">
                                    <div class="col-sm-3">
                                        <label for="ays_monitor_client">
                                            Campaign Monitor <?= __('Client ID', $this->plugin_name) ?>
                                        </label>
                                    </div>
                                    <div class="col-sm-9">
                                        <input type="text"
                                               class="ays-text-input"
                                               id="ays_monitor_client"
                                               name="ays_monitor_client"
                                               value="<?= $monitor_client; ?>"
                                        >
                                    </div>
                                </div>
                                <hr/>
                                <div class="form-group row" aria-describedby="aaa">
                                    <div class="col-sm-3">
                                        <label for="ays_monitor_api_key">
                                            Campaign Monitor <?= __('API Key', $this->plugin_name) ?>
                                        </label>
                                    </div>
                                    <div class="col-sm-9">
                                        <input type="text"
                                               class="ays-text-input"
                                               id="ays_monitor_api_key"
                                               name="ays_monitor_api_key"
                                               value="<?= $monitor_api_key; ?>"
                                        >
                                    </div>
                                </div>
                                <blockquote>
                                    <?= __("You can get your API key and Client ID from your Account Settings page.", $this->plugin_name); ?>
                                </blockquote>
                            </div>
                        </div>
                    </fieldset>
                    <hr/>
                    <fieldset>
                        <legend>
                            <img class="ays_integration_logo" src="<?php echo AYS_QUIZ_ADMIN_URL; ?>/images/integrations/zapier_logo.png" alt="">
                            <h5><?php echo __('Zapier',$this->plugin_name)?></h5>
                        </legend>
                        <div class="form-group row">
                            <div class="col-sm-12">
                                <div class="form-group row" aria-describedby="aaa">
                                    <div class="col-sm-3">
                                        <label for="ays_zapier_hook">
                                            <?= __('Zapier Webhook URL', $this->plugin_name) ?>
                                        </label>
                                    </div>
                                    <div class="col-sm-9">
                                        <input type="text"
                                               class="ays-text-input"
                                               id="ays_zapier_hook"
                                               name="ays_zapier_hook"
                                               value="<?= $zapier_hook; ?>"
                                        >
                                    </div>
                                </div>
                                <blockquote>
                                    <?php echo sprintf(__("If you don’t have any ZAP created, go <a href='%s' target='_blank'> here...</a>.", $this->plugin_name), "https://zapier.com/app/editor/"); ?>
                                </blockquote>
                                <blockquote>
                                    <?= __("We will send you all data from quiz information form with the “AysQuiz” key by POST method.", $this->plugin_name); ?>
                                </blockquote>
                            </div>
                        </div>
                    </fieldset>
                    <hr/>
                    <fieldset>
                        <legend>
                            <img class="ays_integration_logo" src="<?php echo AYS_QUIZ_ADMIN_URL; ?>/images/integrations/activecampaign_logo.png" alt="">
                            <h5><?php echo __('ActiveCampaign',$this->plugin_name)?></h5>
                        </legend>
                        <div class="form-group row">
                            <div class="col-sm-12">
                                <div class="form-group row" aria-describedby="aaa">
                                    <div class="col-sm-3">
                                        <label for="ays_active_camp_url">
                                            <?= __('API Access URL', $this->plugin_name) ?>
                                        </label>
                                    </div>
                                    <div class="col-sm-9">
                                        <input type="text"
                                               class="ays-text-input"
                                               id="ays_active_camp_url"
                                               name="ays_active_camp_url"
                                               value="<?= $active_camp_url; ?>"
                                        >
                                    </div>
                                </div>
                                <hr/>
                                <div class="form-group row" aria-describedby="aaa">
                                    <div class="col-sm-3">
                                        <label for="ays_active_camp_api_key">
                                            <?= __('API Access Key', $this->plugin_name) ?>
                                        </label>
                                    </div>
                                    <div class="col-sm-9">
                                        <input type="text"
                                               class="ays-text-input"
                                               id="ays_active_camp_api_key"
                                               name="ays_active_camp_api_key"
                                               value="<?= $active_camp_api_key; ?>"
                                        >
                                    </div>
                                </div>
                                <blockquote>
                                    <?= __("Your API URL and Key can be found in your account on the My Settings page under the “Developer” tab.", $this->plugin_name); ?>
                                </blockquote>
                            </div>
                        </div>
                    </fieldset>
                    <hr/>
                    <fieldset>
                        <legend>
                            <img class="ays_integration_logo" src="<?php echo AYS_QUIZ_ADMIN_URL; ?>/images/integrations/slack_logo.png" alt="">
                            <h5><?php echo __('Slack',$this->plugin_name)?></h5>
                        </legend>
                        <div class="form-group row">
                            <div class="col-sm-12">
                                <?php if (!$slack_oauth): ?>
                                    <div class="form-group row">
                                        <div class="col-sm-3">
                                            <button id="slackInstructionsPopOver" type="button" class="btn btn-info"
                                                    title="<?= __("Slack Integration Setup Instructions", $this->plugin_name) ?>"><?= __("Instructions", $this->plugin_name) ?></button>
                                            <div class="d-none" id="slackInstructions">
                                                <p><?= sprintf(__("1. You will need to " . "<a href='%s' target='_blank'>%s</a>" . " new Slack App.", $this->plugin_name), "https://api.slack.com/apps?new_app=1", "create"); ?></p>
                                                <p><?= __("2. Complete Project creation for get App credentials.", $this->plugin_name) ?></p>
                                                <p><?= __("3. Next, go to the Features > OAuth & Permissions > Redirect URLs section.", $this->plugin_name) ?></p>
                                                <p><?= __("4. Click Add a new Redirect URL.", $this->plugin_name) ?></p>
                                                <p><?= __("5. In the shown input field, put this value below", $this->plugin_name) ?></p>
                                                <p>
                                                    <code><?= ((!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || $_SERVER['SERVER_PORT'] == 443 ? "https://" : "http://") . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'] . "&oauth=slack" ?></code>
                                                </p>
                                                <p><?= __("6. Then click the Add button.", $this->plugin_name) ?></p>
                                                <p><?= __("7. Then click the Save URLs button.", $this->plugin_name) ?></p>
                                            </div>
                                        </div>
                                    </div>
                                <?php endif; ?>
                                <div class="form-group row">
                                    <div class="col-sm-3">
                                        <label for="ays_slack_client">
                                            <?= __('App Client ID', $this->plugin_name) ?>
                                        </label>
                                    </div>
                                    <div class="col-sm-9">
                                        <input type="text"
                                               class="ays-text-input"
                                               id="ays_slack_client"
                                               name="ays_slack_client"
                                               value="<?= $slack_client; ?>"
                                        >
                                    </div>
                                </div>
                                <hr/>
                                <div class="form-group row">
                                    <div class="col-sm-3">
                                        <label for="ays_slack_oauth">
                                            <?= __('Slack Authorization', $this->plugin_name) ?>
                                        </label>
                                    </div>
                                    <div class="col-sm-9">
                                        <?php if ($slack_oauth): ?>
                                            <span class="btn btn-success pointer-events-none">
                                                <?= __("Authorized", $this->plugin_name) ?></span>
                                        <?php else: ?>
                                            <button type="button" id="slackOAuth2"
                                                    class="btn btn-outline-secondary disabled">
                                                <?= __("Authorize", $this->plugin_name) ?>
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <hr/>
                                <div class="form-group row">
                                    <div class="col-sm-3">
                                        <label for="ays_slack_secret">
                                            <?= __('App Client Secret', $this->plugin_name) ?>
                                        </label>
                                    </div>
                                    <div class="col-sm-9">
                                        <input type="text"
                                               class="ays-text-input"
                                               id="ays_slack_secret"
                                               name="ays_slack_secret"
                                               value="<?= $slack_secret; ?>" <?= $slack_oauth ?: "readonly" ?>
                                        >
                                    </div>
                                </div>
                                <hr/>
                                <div class="form-group row">
                                    <div class="col-sm-3">
                                        <label for="ays_slack_oauth">
                                            <?= __('App Access Token', $this->plugin_name) ?>
                                        </label>
                                    </div>
                                    <div class="col-sm-9">
                                        <?php if ($slack_oauth): ?>
                                            <button type="button"
                                                    data-code="<?= !empty($slack_temp_code) ? $slack_temp_code : "" ?>"
                                                    id="slackOAuthGetToken"
                                                    data-success="<?= __("Access granted", $this->plugin_name) ?>"
                                                    class="btn btn-outline-secondary disabled"><?= __("Get it", $this->plugin_name) ?></button>
                                        <?php else: ?>
                                            <button type="button"
                                                    class="btn btn-outline-secondary disabled"><?= __("Need Authorization", $this->plugin_name) ?>
                                            </button>
                                        <?php endif; ?>
                                        <input type="hidden" id="ays_slack_token" name="ays_slack_token" value="<?= $slack_token; ?>">
                                    </div>
                                </div>
                                <blockquote>
                                    <?= __("You can get your App Client ID and Client Secret from your App’s Basic Information page.", $this->plugin_name); ?>
                                </blockquote>
                            </div>
                        </div>
                    </fieldset>
                    <hr/>
                    <!-- _________________________GOOGLE SHEETS START____________________ -->
                    <fieldset>
                        <legend>
                            <img class="ays_integration_logo" src="<?php echo AYS_QUIZ_ADMIN_URL; ?>/images/integrations/sheets_logo.png" alt="">
                            <h5><?php echo __('Google Sheets',$this->plugin_name)?></h5>
                        </legend>
                        <p style="color: red;"><?php echo $gerror_message; ?></p>
                        <?php if( $google_token ): ?>
                        <div class="form-group row">
                            <div class="col-sm-12">
                                <blockquote>
                                    <span style="margin:0;font-weight:normal;font-style:normal;"><?php
                                        echo sprintf(
                                            __( "You are connected to Google Sheets with %s (%s) account.", $this->plugin_name ),
                                            "<strong><em>" . $google_name . "</em></strong>",
                                            "<a href='mailto:" . $google_email . "'><strong><em>" . $google_email . "</em></strong></a>"
                                        );
                                    ?></span>
                                </blockquote>
                                <br>
                                <input type="submit" class="btn btn-outline-danger" name="ays_disconnect_google_sheets" value="<?php echo __( 'Disconnect', $this->plugin_name ); ?>">
                            </div>
                        </div>
                        <?php else: ?>
                        <div class="form-group row">
                            <div class="col-sm-12">
                                <div class="form-group row" aria-describedby="aaa">
                                    <div class="col-sm-3">
                                        <button id="googleInstructionsPopOver" type="button" class="btn btn-info" data-original-title="Google Integration Setup Instructions" ><?php echo __('Instructions', $this->plugin_name); ?></button>
                                        <div class="d-none" id="googleInstructions">
                                            <p>1. <?php echo __('Turn on Your Google Sheet API', $this->plugin_name); ?>
                                                <a href="https://console.developers.google.com" target="_blank">https://console.developers.google.com</a>
                                            </p>
                                            <p>2. <a href="https://console.developers.google.com/apis/credentials" target="_blank"><?php echo __('Create', $this->plugin_name); ?></a> <?php echo __('new Google Oauth cliend ID credentials (if you do not still have)', $this->plugin_name); ?>.</p>
                                            <p>3. <?php echo __('Choose the application type as <b>Web application</b>', $this->plugin_name); ?></p>
                                            <p>4. <?php echo __('Add the following link in the <b>Authorized redirect URIs</b> field', $this->plugin_name); ?></p>
                                            <p>
                                                <code><?php echo $google_redirect_url?></code>
                                            </p>
                                            <p>5. <?php echo __('Click on the <b>Create</b> button', $this->plugin_name); ?></p>
                                            <p>6. <?php echo __('Copy the <b>Your Cliend ID</b> from the opened popup and paste it in the Google Client ID field.Then click on the Sign In button to complete authorization', $this->plugin_name); ?></p>
                                            <p>7. <?php echo __('After the successful authorization,copy the <b>Your Client ID</b> and paste it in the Google Client ID field again. Also, copy the <b>Your Client Secret</b> and paste it in the Google Client Secret field.', $this->plugin_name); ?></p>
                                            <p>8. <?php echo __('Then click <b>Get token</b> button to get your token', $this->plugin_name); ?></p>
                                            <p>9. <?php echo __('If the token is given successfully, click on the <b>Save Changes</b> button.', $this->plugin_name); ?></p>
                                        </div>
                                    </div>
                                </div>
                                <div class="form-group row">
                                    <div class="col-sm-3">
                                        <label for="ays_google_client">
                                            <?= __('Google Client ID', $this->plugin_name) ?>
                                        </label>
                                    </div>
                                    <div class="col-sm-9">
                                        <input type="text" class="ays-text-input" id="ays_google_client" name="ays_google_client" value="<?= $google_client; ?>" >
                                    </div>
                                </div>
                                <hr/>
                                <div class="form-group row">
                                    <div class="col-sm-3">
                                        <label for="ays_google_secret">
                                            <?= __('Google Client Secret', $this->plugin_name) ?>
                                        </label>
                                    </div>
                                    <div class="col-sm-9">
                                        <input type="text" class="ays-text-input" id="ays_google_secret" name="ays_google_secret" value="">
                                        <input type="hidden" id="ays_google_redirect" name="ays_google_redirect" value="<?php echo $google_redirect_url; ?>">
                                    </div>
                                </div>
                                <hr/>
                                <div class="form-group row">
                                    <div class="col-sm-3"></div>
                                    <div class="col-sm-9">
                                        <button type="submit" name="googleOAuth2" id="googleOAuth2" class="btn btn-outline-info">
                                            <?= __("Connect", $this->plugin_name) ?>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php endif; ?>
                    </fieldset>
                    <!-- __________________________GOOGLE SHEETS END_____________________ -->
                </div>
                <div id="tab3" class="ays-quiz-tab-content <?php echo ($ays_quiz_tab == 'tab3') ? 'ays-quiz-tab-content-active' : ''; ?>">
                    <p class="ays-subtitle"><?php echo __('Shortcodes',$this->plugin_name)?></p>
                    <hr>
                    <fieldset>
                        <legend>
                            <strong style="font-size:30px;">[ ]</strong>
                            <h5><?php echo __('Individual Leaderboard Settings',$this->plugin_name)?></h5>
                        </legend>
                        <blockquote>
                            <?php echo __( "It is designed for a particular quiz’s results.", $this->plugin_name ); ?>
                        </blockquote>
                        <br>
                        <div class="form-group row">
                            <div class="col-sm-4">
                                <label for="ays_invidLead">
                                    <?php echo __( "Shortcode", $this->plugin_name ); ?>
                                    <a class="ays_help" data-toggle="tooltip" title="<?php echo __('You can copy the shortcode and paste it to any post/page to see the list of the top user’s who passed this quiz.',$this->plugin_name)?>">
                                        <i class="ays_fa ays_fa_info_circle"></i>
                                    </a>
                                </label>
                            </div>
                            <div class="col-sm-8">
                                <input type="text" id="ays_invidLead" class="ays-text-input" onclick="this.setSelectionRange(0, this.value.length)" readonly="" value='[ays_quiz_leaderboard id="Your Quiz ID"]'>
                            </div>
                        </div>
                        <div class="form-group row">
                            <div class="col-sm-4">
                                <label for="ays_leadboard_count">
                                    <?php echo __('Users count',$this->plugin_name)?>
                                    <a class="ays_help" data-toggle="tooltip" title="<?php echo __('How many users’ results will be shown in the leaderboard.',$this->plugin_name)?>">
                                        <i class="ays_fa ays_fa_info_circle"></i>
                                    </a>
                                </label>
                            </div>
                            <div class="col-sm-8">
                                <input type="number"
                                    class="ays-text-input"                 
                                    id="ays_leadboard_count" 
                                    name="ays_leadboard_count"
                                    value="<?php echo $ind_leadboard_count; ?>"
                                />
                            </div>
                        </div>
                        <div class="form-group row">
                            <div class="col-sm-4">
                                <label for="ays_leadboard_width">
                                    <?php echo __('Width',$this->plugin_name)?>
                                    <a class="ays_help" data-toggle="tooltip" title="<?php echo __('The width of the Leaderboard box. For 100% leave it blank.',$this->plugin_name)?>">
                                        <i class="ays_fa ays_fa_info_circle"></i>
                                    </a>
                                </label>
                            </div>
                            <div class="col-sm-8">
                                <input type="number"
                                    class="ays-text-input"                 
                                    id="ays_leadboard_width" 
                                    name="ays_leadboard_width"
                                    value="<?php echo $ind_leadboard_width; ?>"
                                />
                                <span style="display:block;" class="ays_quiz_small_hint_text"><?php echo __("For 100% leave blank", $this->plugin_name);?></span>
                            </div>
                        </div>
                        <div class="form-group row">
                            <div class="col-sm-4">
                                <label>
                                    <?php echo __('Group users by',$this->plugin_name)?>
                                    <a class="ays_help" data-toggle="tooltip" title="<?php echo __('Select the way for grouping the results. If you want to make Leaderboard for logged in users, then choose ID. It will collect results by WP user ID. If you want to make Leaderboard for guests, then you need to choose Email and enable Information Form and Email, Name options from quiz settings. It will group results by emails and display guests Names.',$this->plugin_name)?>">
                                        <i class="ays_fa ays_fa_info_circle"></i>
                                    </a>
                                </label>
                            </div>
                            <div class="col-sm-8">
                                <label class="ays_quiz_loader">
                                    <input type="radio" name="ays_leadboard_orderby" value="id" <?php echo $ind_leadboard_orderby == "id" ? "checked" : ""; ?> />
                                    <span><?php echo __( "ID", $this->plugin_name); ?></span>
                                </label>
                                <label class="ays_quiz_loader">
                                    <input type="radio" name="ays_leadboard_orderby" value="email" <?php echo $ind_leadboard_orderby == "email" ? "checked" : ""; ?> />
                                    <span><?php echo __( "Email", $this->plugin_name); ?></span>
                                </label>
                            </div>
                        </div>
                        <div class="form-group row">
                            <div class="col-sm-4">
                                <label>
                                    <?php echo __('Show user’s result',$this->plugin_name)?>
                                    <a class="ays_help" data-toggle="tooltip" title="<?php echo __('Show the users’ Average or Maximum results in the leaderboard.',$this->plugin_name)?>">
                                        <i class="ays_fa ays_fa_info_circle"></i>
                                    </a>
                                </label>
                            </div>
                            <div class="col-sm-8">
                                <label class="ays_quiz_loader">
                                    <input type="radio" name="ays_leadboard_sort" value="avg" <?php echo $ind_leadboard_sort == "avg" ? "checked" : ""; ?> />
                                    <span><?php echo __( "AVG", $this->plugin_name); ?></span>
                                </label>
                                <label class="ays_quiz_loader">
                                    <input type="radio" name="ays_leadboard_sort" value="max" <?php echo $ind_leadboard_sort == "max" ? "checked" : ""; ?> />
                                    <span><?php echo __( "MAX", $this->plugin_name); ?></span>
                                </label>
                            </div>
                        </div>
                        <div class="form-group row">
                            <div class="col-sm-4">
                                <label for="ays_leadboard_color">
                                    <?php echo __('Color',$this->plugin_name)?>
                                    <a class="ays_help" data-toggle="tooltip" title="<?php echo __('Top color of the leaderboard',$this->plugin_name)?>">
                                        <i class="ays_fa ays_fa_info_circle"></i>
                                    </a>
                                </label>
                            </div>
                            <div class="col-sm-8">
                                <input type="text" id="ays_leadboard_color" name="ays_leadboard_color" data-alpha="true" value="<?php echo $ind_leadboard_color; ?>" />
                            </div>
                        </div>
                        <div class="form-group row">
                            <div class="col-sm-4">
                                <label for="ays_leadboard_custom_css">
                                    <?php echo __('Custom CSS',$this->plugin_name)?>
                                    <a class="ays_help" data-toggle="tooltip" title="<?php echo __('Field for entering your own CSS code',$this->plugin_name)?>">
                                        <i class="ays_fa ays_fa_info_circle"></i>
                                    </a>
                                </label>
                            </div>
                            <div class="col-sm-8">
                                <textarea class="ays-textarea" id="ays_leadboard_custom_css" name="ays_leadboard_custom_css" cols="30"
                                      rows="10" style="height: 80px;"><?php echo $ind_leadboard_suctom_css; ?></textarea>
                            </div>
                        </div> <!-- Custom leadboard CSS -->
                        <?php if($empry_dur_count > 0): ?>
                        <div class="form-group row">
                            <div class="col-sm-4">
                                <label>
                                    <?php echo __('Update duration field for old results',$this->plugin_name)?>
                                    <a class="ays_help" data-toggle="tooltip" title="<?php echo __('This button needs to work only once. If you see 0 in the Duration column for some results, please click once to this button and it will regenerate duration for old results. It may happen if you update our plugin from the old version to the latest.',$this->plugin_name)?>">
                                        <i class="ays_fa ays_fa_info_circle"></i>
                                    </a>
                                </label>
                            </div>
                            <div class="col-sm-8">
                                <a class="button" href="?page=<?php echo $_REQUEST['page']; ?>&action=update_duration&ays_quiz_tab=tab3"><?php echo __('Update duration old data', $this->plugin_name); ?></a>
                            </div>
                        </div>
                        <?php endif; ?>
                        <hr>
                        <div class="form-group row">
                            <div class="col-sm-12">
                                <label>
                                    <?php echo __( "Leaderboard Columns", $this->plugin_name ); ?>
                                    <a class="ays_help" data-toggle="tooltip" title="<?php echo __('You can sort table columns and select which columns must display on the front-end.',$this->plugin_name)?>">
                                        <i class="ays_fa ays_fa_info_circle"></i>
                                    </a>
                                </label>
                                <div class="ays-show-user-page-table-wrap">
                                    <ul class="ays-show-user-page-table">
                                        <?php
                                            foreach ($ind_leadboard_columns_order as $key => $val) {
                                                $checked = '';
                                                if(isset($ind_leadboard_columns[$val])){
                                                    $checked = 'checked';
                                                }
                                                if ($val == '') {
                                                   $checked = '';
                                                   $default_leadboard_column_names[$val] = $key;
                                                   $val = $key;
                                                }
                                                ?>
                                                <li class="ays-user-page-option-row ui-state-default">
                                                    <input type="hidden" value="<?php echo $val; ?>" name="ays_ind_leadboard_columns_order[]"/>
                                                    <input type="checkbox" id="ays_ilb_show_<?php echo $val; ?>" value="<?php echo $val; ?>" class="ays-checkbox-input" name="ays_ind_leadboard_columns[<?php echo $val; ?>]" <?php echo $checked; ?>/>
                                                    <label for="ays_ilb_show_<?php echo $val; ?>">
                                                        <?php echo $default_leadboard_column_names[$val]; ?>
                                                    </label>
                                                </li>
                                                <?php
                                            }
                                         ?>
                                    </ul>
                               </div>
                            </div>
                        </div>
                    </fieldset>
                    <hr>
                    <fieldset>
                        <legend>
                            <strong style="font-size:30px;">[ ]</strong>
                            <h5 class="ays-subtitle"><?php echo __('Global Leaderboard Settings',$this->plugin_name)?></h5>
                        </legend>
                        <blockquote>
                            <?php echo __( "It is designed for all quizzes results.", $this->plugin_name ); ?>
                        </blockquote>
                        <hr>
                        <div class="form-group row">
                            <div class="col-sm-4">
                                <label for="ays_globLead">
                                    <?php echo __( "Shortcode", $this->plugin_name ); ?>
                                    <a class="ays_help" data-toggle="tooltip" title="<?php echo __('You can copy the shortcode and paste it to any post/page to see the list of the top user’s who passed any quiz.',$this->plugin_name)?>">
                                        <i class="ays_fa ays_fa_info_circle"></i>
                                    </a>
                                </label>
                            </div>
                            <div class="col-sm-8">
                                <input type="text" id="ays_globLead" class="ays-text-input" onclick="this.setSelectionRange(0, this.value.length)" readonly="" value='[ays_quiz_gleaderboard]'>
                            </div>
                        </div>
                        <div class="form-group row">
                            <div class="col-sm-4">
                                <label for="ays_gleadboard_count">
                                    <?php echo __('Users count',$this->plugin_name)?>
                                    <a class="ays_help" data-toggle="tooltip" title="<?php echo __('How many users’ results will be shown in the leaderboard.',$this->plugin_name)?>">
                                        <i class="ays_fa ays_fa_info_circle"></i>
                                    </a>
                                </label>
                            </div>
                            <div class="col-sm-8">
                                <input type="number"
                                    class="ays-text-input"                 
                                    id="ays_gleadboard_count" 
                                    name="ays_gleadboard_count"
                                    value="<?php echo $glob_leadboard_count; ?>"
                                />
                            </div>
                        </div>
                        <div class="form-group row">
                            <div class="col-sm-4">
                                <label for="ays_gleadboard_width">
                                    <?php echo __('Width',$this->plugin_name)?>
                                    <a class="ays_help" data-toggle="tooltip" title="<?php echo __('The width of the Leaderboard box. It accepts only numeric values. For 100% leave it blank.',$this->plugin_name)?>">
                                        <i class="ays_fa ays_fa_info_circle"></i>
                                    </a>
                                </label>
                            </div>
                            <div class="col-sm-8">
                                <input type="number"
                                    class="ays-text-input"                 
                                    id="ays_gleadboard_width" 
                                    name="ays_gleadboard_width"
                                    value="<?php echo $glob_leadboard_width; ?>"
                                />
                                <span style="display:block;" class="ays_quiz_small_hint_text"><?php echo __("For 100% leave blank", $this->plugin_name);?></span>
                            </div>
                        </div>
                        <div class="form-group row">
                            <div class="col-sm-4">
                                <label>
                                    <?php echo __('Group users by',$this->plugin_name)?>
                                    <a class="ays_help" data-toggle="tooltip" title="<?php echo __('Select the way for grouping the results. If you want to make Leaderboard for logged in users, then choose ID. It will collect results by WP user ID. If you want to make Leaderboard for guests, then you need to choose Email and enable Information Form and Email, Name options from quiz settings. It will group results by emails and display guests Names.',$this->plugin_name)?>">
                                        <i class="ays_fa ays_fa_info_circle"></i>
                                    </a>
                                </label>
                            </div>
                            <div class="col-sm-8">
                                <label class="ays_quiz_loader">
                                    <input type="radio" name="ays_gleadboard_orderby" value="id" <?php echo $glob_leadboard_orderby == "id" ? "checked" : ""; ?> />
                                    <span><?php echo __( "ID", $this->plugin_name); ?></span>
                                </label>
                                <label class="ays_quiz_loader">
                                    <input type="radio" name="ays_gleadboard_orderby" value="email" <?php echo $glob_leadboard_orderby == "email" ? "checked" : ""; ?> />
                                    <span><?php echo __( "Email", $this->plugin_name); ?></span>
                                </label>
                            </div>
                        </div>
                        <div class="form-group row">
                            <div class="col-sm-4">
                                <label>
                                    <?php echo __('Show user’s result',$this->plugin_name)?>
                                    <a class="ays_help" data-toggle="tooltip" title="<?php echo __('Show the users’ Average, Maximum or Sum results in the leaderboard. SUM does not work with Score(table column)',$this->plugin_name)?>">
                                        <i class="ays_fa ays_fa_info_circle"></i>
                                    </a>
                                </label>
                            </div>
                            <div class="col-sm-8">
                                <label class="ays_quiz_loader">
                                    <input type="radio" name="ays_gleadboard_sort" value="avg" <?php echo $glob_leadboard_sort == "avg" ? "checked" : ""; ?> />
                                    <span><?php echo __( "AVG", $this->plugin_name); ?></span>
                                </label>
                                <label class="ays_quiz_loader">
                                    <input type="radio" name="ays_gleadboard_sort" value="max" <?php echo $glob_leadboard_sort == "max" ? "checked" : ""; ?> />
                                    <span><?php echo __( "MAX", $this->plugin_name); ?></span>
                                </label>
                                <label class="ays_quiz_loader">
                                    <input type="radio" name="ays_gleadboard_sort" value="sum" <?php echo $glob_leadboard_sort == "sum" ? "checked" : ""; ?> />
                                    <span><?php echo __( "SUM", $this->plugin_name); ?></span>
                                </label>
                            </div>
                        </div>
                        <div class="form-group row">
                            <div class="col-sm-4">
                                <label for="ays_gleadboard_color">
                                    <?php echo __('Color',$this->plugin_name)?>
                                    <a class="ays_help" data-toggle="tooltip" title="<?php echo __('Top color of the leaderboard',$this->plugin_name)?>">
                                        <i class="ays_fa ays_fa_info_circle"></i>
                                    </a>
                                </label>
                            </div>
                            <div class="col-sm-8">
                                <input type="text" id="ays_gleadboard_color" name="ays_gleadboard_color" data-alpha="true" value="<?php echo $glob_leadboard_color; ?>" />
                            </div>
                        </div>
                        <div class="form-group row">
                            <div class="col-sm-4">
                                <label for="ays_gleadboard_custom_css">
                                    <?php echo __('Custom CSS',$this->plugin_name)?>
                                    <a class="ays_help" data-toggle="tooltip" title="<?php echo __('Field for entering your own CSS code',$this->plugin_name)?>">
                                        <i class="ays_fa ays_fa_info_circle"></i>
                                    </a>
                                </label>
                            </div>
                            <div class="col-sm-8">
                                <textarea class="ays-textarea" id="ays_gleadboard_custom_css" name="ays_gleadboard_custom_css" cols="30"
                                      rows="10" style="height: 80px;"><?php echo $glob_leadboard_suctom_css; ?></textarea>
                            </div>
                        </div> <!-- Custom global leadboard CSS -->
                        <?php if($empry_dur_count > 0): ?>
                        <div class="form-group row">
                            <div class="col-sm-4">
                                <label>
                                    <?php echo __('Update duration field for old results',$this->plugin_name)?>
                                    <a class="ays_help" data-toggle="tooltip" title="<?php echo __('This button needs to work only once. If you see 0 in the Duration column for some results, please click once to this button and it will regenerate duration for old results. It may happen if you update our plugin from the old version to the latest.',$this->plugin_name)?>">
                                        <i class="ays_fa ays_fa_info_circle"></i>
                                    </a>
                                </label>
                            </div>
                            <div class="col-sm-8">
                                <a class="button" href="?page=<?php echo $_REQUEST['page']; ?>&action=update_duration&ays_quiz_tab=tab3"><?php echo __('Update duration old data', $this->plugin_name); ?></a>
                            </div>
                        </div>
                        <?php endif; ?>
                        <hr>
                        <div class="form-group row">
                            <div class="col-sm-12">
                                <label>
                                    <?php echo __( "Leaderboard Columns", $this->plugin_name ); ?>
                                    <a class="ays_help" data-toggle="tooltip" title="<?php echo __('You can sort table columns and select which columns must display on the front-end.',$this->plugin_name)?>">
                                        <i class="ays_fa ays_fa_info_circle"></i>
                                    </a>
                                </label>
                                <div class="ays-show-user-page-table-wrap">
                                    <ul class="ays-show-user-page-table">
                                        <?php
                                            foreach ($glob_leadboard_columns_order as $key => $val) {
                                                $checked = '';
                                                if(isset($glob_leadboard_columns[$val])){
                                                    $checked = 'checked';
                                                }
                                                if ($val == '') {
                                                   $checked = '';
                                                   $default_leadboard_column_names[$val] = $key;
                                                   $val = $key;
                                                }
                                                ?>
                                                <li class="ays-user-page-option-row ui-state-default">
                                                    <input type="hidden" value="<?php echo $val; ?>" name="ays_glob_leadboard_columns_order[]"/>
                                                    <input type="checkbox" id="ays_glb_show_<?php echo $val; ?>" value="<?php echo $val; ?>" class="ays-checkbox-input" name="ays_glob_leadboard_columns[<?php echo $val; ?>]" <?php echo $checked; ?>/>
                                                    <label for="ays_glb_show_<?php echo $val; ?>">
                                                        <?php echo $default_leadboard_column_names[$val]; ?>
                                                    </label>
                                                </li>
                                                <?php
                                            }
                                         ?>
                                    </ul>
                               </div>
                            </div>
                        </div>
                    </fieldset>
                    <hr/>
                    <fieldset>
                        <legend>
                            <strong style="font-size:30px;">[ ]</strong>
                            <h5><?php echo __('User Page Settings',$this->plugin_name)?></h5>
                        </legend>
                        <div class="form-group row">
                            <div class="col-sm-4">
                                <label for="ays_user_page">
                                    <?php echo __( "Shortcode", $this->plugin_name ); ?>
                                    <a class="ays_help" data-toggle="tooltip" title="<?php echo __('You can copy the shortcode and insert it to any post to show the current user’s results history.',$this->plugin_name)?>">
                                        <i class="ays_fa ays_fa_info_circle"></i>
                                    </a>
                                </label>
                            </div>
                            <div class="col-sm-8">
                                <input type="text" id="ays_user_page" class="ays-text-input" onclick="this.setSelectionRange(0, this.value.length)" readonly="" value='[ays_user_page]'>
                            </div>
                        </div>
                        <hr>
                        <div class="form-group row">
                            <div class="col-sm-12">
                                <label>
                                    <?php echo __( "User Page results table columns", $this->plugin_name ); ?>
                                    <a class="ays_help" data-toggle="tooltip" title="<?php echo __('You can sort table columns and select which columns must display on the front-end.',$this->plugin_name)?>">
                                        <i class="ays_fa ays_fa_info_circle"></i>
                                    </a>
                                </label>
                                <div class="ays-show-user-page-table-wrap">
                                    <ul class="ays-show-user-page-table">
                                        <?php
                                            foreach ($user_page_columns_order as $key => $val) {
                                                $checked = '';
                                                if(isset($user_page_columns[$val])){
                                                    $checked = 'checked';
                                                }
                                                ?>
                                                <li class="ays-user-page-option-row ui-state-default">
                                                    <input type="hidden" value="<?php echo $val; ?>" name="ays_user_page_columns_order[]"/>
                                                    <input type="checkbox" id="ays_show_<?php echo $val; ?>" value="<?php echo $val; ?>" class="ays-checkbox-input" name="ays_user_page_columns[<?php echo $val; ?>]" <?php echo $checked; ?>/>
                                                    <label for="ays_show_<?php echo $val; ?>">
                                                        <?php echo $default_user_page_column_names[$val]; ?>
                                                    </label>
                                                </li>
                                                <?php
                                            }
                                         ?>
                                    </ul>
                               </div>
                            </div>
                        </div>
                    </fieldset>
                    <hr/>
                    <!-- Show all result start -->
                    <fieldset>
                        <legend>
                            <strong style="font-size:30px;">[ ]</strong>
                            <h5><?php echo __('All Results Settings',$this->plugin_name)?></h5>
                        </legend>
                        <div class="form-group row">
                            <div class="col-sm-4">
                                <label for="ays_all_results">
                                    <?php echo __( "Shortcode", $this->plugin_name ); ?>
                                    <a class="ays_help" data-toggle="tooltip" title="<?php echo __('You can copy the shortcode and insert it to any post to show all results.',$this->plugin_name)?>">
                                        <i class="ays_fa ays_fa_info_circle"></i>
                                    </a>
                                </label>
                            </div>
                            <div class="col-sm-8">
                                <input type="text" id="ays_all_results" class="ays-text-input" onclick="this.setSelectionRange(0, this.value.length)" readonly="" value='[ays_all_results]'>
                            </div>
                        </div>
                        <hr>
                        <div class="form-group row">
                            <div class="col-sm-4">
                                <label for="ays_all_results_show_publicly">
                                    <?php echo __( "Show to guests too", $this->plugin_name ); ?>
                                    <a class="ays_help" data-toggle="tooltip" title="<?php echo __('Show the All results table to guests as well. By default, it is displayed only for logged-in users. If this option is disabled, then only the logged-in users will be able to see the table. Note: Despite the fact of showing the table to the guests, the table will contain only info of the logged-in users.',$this->plugin_name)?>">
                                        <i class="ays_fa ays_fa_info_circle"></i>
                                    </a>
                                </label>
                            </div>
                            <div class="col-sm-8">
                                <input type="checkbox" class="ays-checkbox-input" id="ays_all_results_show_publicly" name="ays_all_results_show_publicly" value="on" <?php echo $all_results_show_publicly ? 'checked' : ''; ?> />
                            </div>
                        </div>
                        <hr>
                        <div class="form-group row">
                            <div class="col-sm-12">
                                <label>
                                    <?php echo __( "Table columns", $this->plugin_name ); ?>
                                    <a class="ays_help" data-toggle="tooltip" title="<?php echo __('You can sort table columns and select which columns must display on the front-end.',$this->plugin_name)?>">
                                        <i class="ays_fa ays_fa_info_circle"></i>
                                    </a>
                                </label>
                                <div class="ays-show-user-page-table-wrap">
                                    <ul class="ays-show-user-page-table">
                                        <?php
                                            foreach ($all_results_columns_order as $key => $val) {
                                                $checked = '';
                                                if(isset($all_results_columns[$val])){
                                                    $checked = 'checked';
                                                }
                                                ?>
                                                <li class="ays-user-page-option-row ui-state-default">
                                                    <input type="hidden" value="<?php echo $val; ?>" name="ays_all_results_columns_order[]"/>
                                                    <input type="checkbox" id="ays_show_result<?php echo $val; ?>" value="<?php echo $val; ?>" class="ays-checkbox-input" name="ays_all_results_columns[<?php echo $val; ?>]" <?php echo $checked; ?>/>
                                                    <label for="ays_show_result<?php echo $val; ?>">
                                                        <?php echo $default_all_results_column_names[$val]; ?>
                                                    </label>
                                                </li>
                                                <?php
                                            }
                                         ?>
                                    </ul>
                               </div>
                            </div>
                        </div>
                    </fieldset>
                    <!-- show result end -->
                </div>
                <div id="tab4" class="ays-quiz-tab-content <?php echo ($ays_quiz_tab == 'tab4') ? 'ays-quiz-tab-content-active' : ''; ?>">
                    <p class="ays-subtitle">
                        <?php echo __('Message variables',$this->plugin_name)?>
                        <a class="ays_help" data-toggle="tooltip" data-html="true" title="<p style='margin-bottom:3px;'><?php echo __( 'You can copy these variables and paste them in the following options from the quiz settings', $this->plugin_name ); ?>:</p>
                            <p style='padding-left:10px;margin:0;'>- <?php echo __( 'Result message', $this->plugin_name ); ?></p>
                            <p style='padding-left:10px;margin:0;'>- <?php echo __( 'Quiz pass message', $this->plugin_name ); ?></p>
                            <p style='padding-left:10px;margin:0;'>- <?php echo __( 'Quiz fail message', $this->plugin_name ); ?></p>
                            <p style='padding-left:10px;margin:0;'>- <?php echo __( 'Mail Message', $this->plugin_name ); ?></p>
                            <p style='padding-left:10px;margin:0;'>- <?php echo __( 'Certificate title', $this->plugin_name ); ?></p>
                            <p style='padding-left:10px;margin:0;'>- <?php echo __( 'Certificate body', $this->plugin_name ); ?></p>
                            <p style='padding-left:10px;margin:0;'>- <?php echo __( 'Interval message', $this->plugin_name ); ?></p>
                            <p style='padding-left:10px;margin:0;'>- <?php echo __( 'Email configuration', $this->plugin_name ); ?></p>
                            <p style='text-indent:30px;padding-left:10px;margin:0;'>* <?php echo __( 'From Name', $this->plugin_name ); ?></p>
                            <p style='text-indent:30px;padding-left:10px;margin:0;'>* <?php echo __( 'Subject', $this->plugin_name ); ?></p>
                            <p style='text-indent:30px;padding-left:10px;margin:0;'>* <?php echo __( 'Reply To Name', $this->plugin_name ); ?></p>">
                            <i class="ays_fa ays_fa_info_circle"></i>
                        </a>
                    </p>
                    <blockquote>
                        <p><?php echo __( "You can copy these variables and paste them in the following options from the quiz settings", $this->plugin_name ); ?>:</p>
                        <p style="text-indent:10px;margin:0;">- <?php echo __( "Result message", $this->plugin_name ); ?></p>
                        <p style="text-indent:10px;margin:0;">- <?php echo __( "Quiz pass message", $this->plugin_name ); ?></p>
                        <p style="text-indent:10px;margin:0;">- <?php echo __( "Quiz fail message", $this->plugin_name ); ?></p>
                        <p style="text-indent:10px;margin:0;">- <?php echo __( "Mail Message", $this->plugin_name ); ?></p>
                        <p style="text-indent:10px;margin:0;">- <?php echo __( "Certificate title", $this->plugin_name ); ?></p>
                        <p style="text-indent:10px;margin:0;">- <?php echo __( "Certificate body", $this->plugin_name ); ?></p>
                        <p style="text-indent:10px;margin:0;">- <?php echo __( "Interval message", $this->plugin_name ); ?></p>
                        <p style="text-indent:10px;margin:0;">- <?php echo __( "Email configuration", $this->plugin_name ); ?></p>
                        <p style="text-indent:30px;margin:0;">* <?php echo __( "From Name", $this->plugin_name ); ?></p>
                        <p style="text-indent:30px;margin:0;">* <?php echo __( "Subject", $this->plugin_name ); ?></p>
                        <p style="text-indent:30px;margin:0;">* <?php echo __( "Reply To Name", $this->plugin_name ); ?></p>
                    </blockquote>
                    <hr>
                    <div class="form-group row">
                        <div class="col-sm-12">
                            <p class="vmessage">
                                <strong>
                                    <input type="text" onClick="this.setSelectionRange(0, this.value.length)" readonly value="%%user_name%%"/>
                                </strong>
                                <span> - </span>
                                <span style="font-size:18px;">
                                    <?php echo __( "The name the user entered into information form", $this->plugin_name); ?>
                                </span>
                            </p>
                            <p class="vmessage">
                                <strong>
                                    <input type="text" onClick="this.setSelectionRange(0, this.value.length)" readonly value="%%user_email%%" />
                                </strong>
                                <span> - </span>
                                <span style="font-size:18px;">
                                    <?php echo __( "The E-mail the user entered into information form", $this->plugin_name); ?>
                                </span>
                            </p>
                            <p class="vmessage">
                                <strong>
                                    <input type="text" onClick="this.setSelectionRange(0, this.value.length)" readonly value="%%quiz_name%%" />
                                </strong>
                                <span> - </span>
                                <span style="font-size:18px;">
                                    <?php echo __( "The title of the quiz", $this->plugin_name); ?>
                                </span>
                            </p>
                            <p class="vmessage">
                                <strong>
                                    <input type="text" onClick="this.setSelectionRange(0, this.value.length)" readonly value="%%score%%" />
                                </strong>
                                <span> - </span>
                                <span style="font-size:18px;">
                                    <?php echo __( "The score of quiz which got the user", $this->plugin_name); ?>
                                </span>
                            </p>
                            <p class="vmessage">
                                <strong>
                                    <input type="text" onClick="this.setSelectionRange(0, this.value.length)" readonly value="%%user_points%%" />
                                </strong>
                                <span> - </span>
                                <span style="font-size:18px;">
                                    <?php echo __( "The points of quiz which got the user", $this->plugin_name); ?>
                                </span>
                            </p>
                            <p class="vmessage">
                                <strong>
                                    <input type="text" onClick="this.setSelectionRange(0, this.value.length)" readonly value="%%user_corrects_count%%" />
                                </strong>
                                <span> - </span>
                                <span style="font-size:18px;">
                                    <?php echo __( "The number of correct answers of the user", $this->plugin_name); ?>
                                </span>
                            </p>
                            <p class="vmessage">
                                <strong>
                                    <input type="text" onClick="this.setSelectionRange(0, this.value.length)" readonly value="%%questions_count%%" />
                                </strong>
                                <span> - </span>
                                <span style="font-size:18px;">
                                    <?php echo __( "The number of questions that the user must pass.", $this->plugin_name); ?>
                                </span>
                            </p>
                            <p class="vmessage">
                                <strong>
                                    <input type="text" onClick="this.setSelectionRange(0, this.value.length)" readonly value="%%max_points%%" />
                                </strong>
                                <span> - </span>
                                <span style="font-size:18px;">
                                    <?php echo __( "Maximum points which can get the user", $this->plugin_name); ?>
                                </span>
                            </p>
                            <p class="vmessage">
                                <strong>
                                    <input type="text" onClick="this.setSelectionRange(0, this.value.length)" readonly value="%%current_date%%" />
                                </strong>
                                <span> - </span>
                                <span style="font-size:18px;">
                                    <?php echo __( "The date of the passing quiz", $this->plugin_name); ?>
                                </span>
                            </p>
                            <p class="vmessage">
                                <strong>
                                    <input type="text" onClick="this.setSelectionRange(0, this.value.length)" readonly value="%%quiz_logo%%" />
                                </strong>
                                <span> - </span>
                                <span style="font-size:18px;">
                                    <?php echo __( "The quiz image which used for quiz start page", $this->plugin_name); ?>
                                </span>
                            </p>
                            <p class="vmessage">
                                <strong>
                                    <input type="text" onClick="this.setSelectionRange(0, this.value.length)" readonly value="%%interval_message%%" />
                                </strong>
                                <span> - </span>
                                <span style="font-size:18px;">
                                    <?php echo __( "The message which must display on the result page depending from score", $this->plugin_name); ?>
                                </span>
                            </p>
                            <p class="vmessage">
                                <strong>
                                    <input type="text" onClick="this.setSelectionRange(0, this.value.length)" readonly value="%%avg_score%%" />
                                </strong>
                                <span> - </span>
                                <span style="font-size:18px;">
                                    <?php echo __( "The average score of the quiz of all time", $this->plugin_name); ?>
                                </span>
                            </p>
                            <p class="vmessage">
                                <strong>
                                    <input type="text" onClick="this.setSelectionRange(0, this.value.length)" readonly value="%%avg_rate%%" />
                                </strong>
                                <span> - </span>
                                <span style="font-size:18px;">
                                    <?php echo __( "The average rate of the quiz of all time", $this->plugin_name); ?>
                                </span>
                            </p>
                            <p class="vmessage">
                                <strong>
                                    <input type="text" onClick="this.setSelectionRange(0, this.value.length)" readonly value="%%user_pass_time%%" />
                                </strong>
                                <span> - </span>
                                <span style="font-size:18px;">
                                    <?php echo __( "The time which spent that the user passed the quiz", $this->plugin_name); ?>
                                </span>
                            </p>
                            <p class="vmessage">
                                <strong>
                                    <input type="text" onClick="this.setSelectionRange(0, this.value.length)" readonly value="%%quiz_time%%" />
                                </strong>
                                <span> - </span>
                                <span style="font-size:18px;">
                                    <?php echo __( "The time which must spend the user to the quiz", $this->plugin_name); ?>
                                </span>
                            </p>
                            <p class="vmessage">
                                <strong>
                                    <input type="text" onClick="this.setSelectionRange(0, this.value.length)" readonly value="%%results_by_cats%%" />
                                </strong>
                                <span> - </span>
                                <span style="font-size:18px;">
                                    <?php echo __( "The score of the quiz by a question categories which got the user", $this->plugin_name); ?>
                                </span>
                            </p>
                            <p class="vmessage">
                                <strong>
                                    <input type="text" onClick="this.setSelectionRange(0, this.value.length)" readonly value="%%unique_code%%" />
                                </strong>
                                <span> - </span>
                                <span style="font-size:18px;">
                                    <?php echo __( "You can use this unique code as an identifier. It is unique for every attempt.", $this->plugin_name); ?>
                                </span>
                            </p>
                            <p class="vmessage">
                                <strong>
                                    <input type="text" onClick="this.setSelectionRange(0, this.value.length)" readonly value="%%download_certificate%%" />
                                </strong>
                                <span> - </span>
                                <span style="font-size:18px;">
                                    <?php echo __( "You can use this variable to allow users to download their certificate after quiz completion.", $this->plugin_name); ?>
                                </span>
                            </p>
                            <p class="vmessage">
                                <strong>
                                    <input type="text" onClick="this.setSelectionRange(0, this.value.length)" readonly value="%%wrong_answers_count%%" />
                                </strong>
                                <span> - </span>
                                <span style="font-size:18px;">
                                    <?php echo __( "The number of wrong answers of the user.", $this->plugin_name); ?>
                                </span>
                            </p>
                            <p class="vmessage">
                                <strong>
                                    <input type="text" onClick="this.setSelectionRange(0, this.value.length)" readonly value="%%avg_score_by_category%%" />
                                </strong>
                                <span> - </span>
                                <span style="font-size:18px;">
                                    <?php echo __( "The average score by the question category of the given quiz of the given user.", $this->plugin_name); ?>
                                </span>
                            </p>
                            <p class="vmessage">
                                <strong>
                                    <input type="text" onClick="this.setSelectionRange(0, this.value.length)" readonly value="%%not_answered_count%%" />
                                </strong>
                                <span> - </span>
                                <span style="font-size:18px;">
                                    <?php echo __( "The number of not answered of the user.", $this->plugin_name); ?>
                                </span>
                            </p>
                        </div>
                    </div>
                </div>
                <div id="tab5" class="ays-quiz-tab-content <?php echo ($ays_quiz_tab == 'tab5') ? 'ays-quiz-tab-content-active' : ''; ?>">
                    <p class="ays-subtitle">
                        <?php echo __('Buttons texts',$this->plugin_name)?>
                        <a class="ays_help" data-toggle="tooltip" data-html="true" title="<p style='margin-bottom:3px;'><?php echo __( 'If you make a change here, these words will not be translated!', $this->plugin_name ); ?>">
                            <i class="ays_fa ays_fa_info_circle"></i>
                        </a>
                    </p>
                    <blockquote class="ays_warning">
                        <p style="margin:0;"><?php echo __( "If you make a change here, these words will not be translated!", $this->plugin_name ); ?></p>
                    </blockquote>
                    <hr>
                    <div class="form-group row">
                        <div class="col-sm-3">
                            <label for="ays_start_button">
                                <?php echo __( "Start button", $this->plugin_name ); ?>
                            </label>
                        </div>
                        <div class="col-sm-9">
                            <input type="text" id="ays_start_button" name="ays_start_button" class="ays-text-input ays-text-input-short"  value='<?php echo $start_button; ?>'>
                        </div>
                    </div>
                    <div class="form-group row">
                        <div class="col-sm-3">
                            <label for="ays_next_button">
                                <?php echo __( "Next button", $this->plugin_name ); ?>
                            </label>
                        </div>
                        <div class="col-sm-9">
                            <input type="text" id="ays_next_button" name="ays_next_button" class="ays-text-input ays-text-input-short"  value='<?php echo $next_button; ?>'>
                        </div>
                    </div>
                    <div class="form-group row">
                        <div class="col-sm-3">
                            <label for="ays_previous_button">
                                <?php echo __( "Previous button", $this->plugin_name ); ?>
                            </label>
                        </div>
                        <div class="col-sm-9">
                            <input type="text" id="ays_previous_button" name="ays_previous_button" class="ays-text-input ays-text-input-short"  value='<?php echo $previous_button; ?>'>
                        </div>
                    </div>
                    <div class="form-group row">
                        <div class="col-sm-3">
                            <label for="ays_clear_button">
                                <?php echo __( "Clear button", $this->plugin_name ); ?>
                            </label>
                        </div>
                        <div class="col-sm-9">
                            <input type="text" id="ays_clear_button" name="ays_clear_button" class="ays-text-input ays-text-input-short"  value='<?php echo $clear_button; ?>'>
                        </div>
                    </div>
                    <div class="form-group row">
                        <div class="col-sm-3">
                            <label for="ays_finish_button">
                                <?php echo __( "Finish button", $this->plugin_name ); ?>
                            </label>
                        </div>
                        <div class="col-sm-9">
                            <input type="text" id="ays_finish_button" name="ays_finish_button" class="ays-text-input ays-text-input-short"  value='<?php echo $finish_button; ?>'>
                        </div>
                    </div>
                    <div class="form-group row">
                        <div class="col-sm-3">
                            <label for="ays_see_result_button">
                                <?php echo __( "See Result button", $this->plugin_name ); ?>
                            </label>
                        </div>
                        <div class="col-sm-9">
                            <input type="text" id="ays_see_result_button" name="ays_see_result_button" class="ays-text-input ays-text-input-short"  value='<?php echo $see_result_button; ?>'>
                        </div>
                    </div>
                    <div class="form-group row">
                        <div class="col-sm-3">
                            <label for="ays_restart_quiz_button">
                                <?php echo __( "Restart quiz button", $this->plugin_name ); ?>
                            </label>
                        </div>
                        <div class="col-sm-9">
                            <input type="text" id="ays_restart_quiz_button" name="ays_restart_quiz_button" class="ays-text-input ays-text-input-short"  value='<?php echo $restart_quiz_button; ?>'>
                        </div>
                    </div>
                    <div class="form-group row">
                        <div class="col-sm-3">
                            <label for="ays_send_feedback_button">
                                <?php echo __( "Send feedback button", $this->plugin_name ); ?>
                            </label>
                        </div>
                        <div class="col-sm-9">
                            <input type="text" id="ays_send_feedback_button" name="ays_send_feedback_button" class="ays-text-input ays-text-input-short"  value='<?php echo $send_feedback_button; ?>'>
                        </div>
                    </div>
                    <div class="form-group row">
                        <div class="col-sm-3">
                            <label for="ays_load_more_button">
                                <?php echo __( "Load more button", $this->plugin_name ); ?>
                            </label>
                        </div>
                        <div class="col-sm-9">
                            <input type="text" id="ays_load_more_button" name="ays_load_more_button" class="ays-text-input ays-text-input-short"  value='<?php echo $load_more_button; ?>'>
                        </div>
                    </div>
                    <div class="form-group row">
                        <div class="col-sm-3">
                            <label for="ays_exit_button">
                                <?php echo __( "Exit button", $this->plugin_name ); ?>
                            </label>
                        </div>
                        <div class="col-sm-9">
                            <input type="text" id="ays_exit_button" name="ays_exit_button" class="ays-text-input ays-text-input-short"  value='<?php echo $exit_button; ?>'>
                        </div>
                    </div>
                    <div class="form-group row">
                        <div class="col-sm-3">
                            <label for="ays_check_button">
                                <?php echo __( "Check button", $this->plugin_name ); ?>
                            </label>
                        </div>
                        <div class="col-sm-9">
                            <input type="text" id="ays_check_button" name="ays_check_button" class="ays-text-input ays-text-input-short"  value='<?php echo $check_button; ?>'>
                        </div>
                    </div>
                    <div class="form-group row">
                        <div class="col-sm-3">
                            <label for="ays_login_button">
                                <?php echo __( "Log In button", $this->plugin_name ); ?>
                            </label>
                        </div>
                        <div class="col-sm-9">
                            <input type="text" id="ays_login_button" name="ays_login_button" class="ays-text-input ays-text-input-short"  value='<?php echo $login_button; ?>'>
                        </div>
                    </div>
                </div>
            </div>
            </div>
            <hr/>
            <div style="position:sticky;padding:15px 0px;bottom:0;">
            <?php
                wp_nonce_field('settings_action', 'settings_action');
                $other_attributes = array();
                submit_button(__('Save changes', $this->plugin_name), 'primary', 'ays_submit', true, $other_attributes);
            ?>
            </div>
        </form>
    </div>
</div>
