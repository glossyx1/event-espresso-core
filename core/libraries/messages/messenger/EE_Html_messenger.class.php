<?php
if (!defined('EVENT_ESPRESSO_VERSION')) {
    exit('NO direct script access allowed');
}


/**
 * EE_Html_messenger class
 *
 * @since              4.3.0
 * @package            Event Espresso
 * @subpackage         messages
 * @author             Darren Ethier
 */
class EE_Html_messenger extends EE_messenger
{


    /**
     * The following are the properties that this messenger requires for displaying the html
     */
    /**
     * This is the html body generated by the template via the message type.
     *
     * @var string
     */
    protected $_content;


    /**
     * This is for the page title that gets displayed.  (Why use "subject"?  Because the "title" tag in html is
     * equivalent to the "subject" of the page.
     *
     * @var string
     */
    protected $_subject;


    /**
     * EE_Html_messenger constructor.
     */
    public function __construct()
    {
        //set properties
        $this->name = 'html';
        $this->description = __('This messenger outputs a message to a browser for display.', 'event_espresso');
        $this->label = array(
            'singular' => __('html', 'event_espresso'),
            'plural' => __('html', 'event_espresso'),
        );
        $this->activate_on_install = true;
        // add the "powered by EE" credit link to the HTML receipt and invoice
        add_filter(
            'FHEE__EE_Html_messenger___send_message__main_body',
            array($this, 'add_powered_by_credit_link_to_receipt_and_invoice'),
            10,
            3
        );
        parent::__construct();
    }


    /**
     * HTML Messenger desires execution immediately.
     *
     * @see    parent::send_now() for documentation.
     * @since  4.9.0
     * @return bool
     */
    public function send_now()
    {
        return true;
    }


    /**
     * HTML Messenger allows an empty to field.
     *
     * @see    parent::allow_empty_to_field() for documentation
     * @since  4.9.0
     * @return bool
     */
    public function allow_empty_to_field()
    {
        return true;
    }


    /**
     * @see abstract declaration in EE_messenger for details.
     */
    protected function _set_admin_pages()
    {
        $this->admin_registered_pages = array('events_edit' => true);
    }


    /**
     * @see abstract declaration in EE_messenger for details.
     */
    protected function _set_valid_shortcodes()
    {
        $this->_valid_shortcodes = array();
    }


    /**
     * @see abstract declaration in EE_messenger for details.
     */
    protected function _set_validator_config()
    {
        $this->_validator_config = array(
            'subject' => array(
                'shortcodes' => array('organization', 'primary_registration_details', 'email', 'transaction'),
            ),
            'content' => array(
                'shortcodes' => array(
                    'organization',
                    'primary_registration_list',
                    'primary_registration_details',
                    'email',
                    'transaction',
                    'event_list',
                    'payment_list',
                    'venue',
                    'line_item_list',
                    'messenger',
                    'ticket_list',
                ),
            ),
            'event_list' => array(
                'shortcodes' => array(
                    'event',
                    'ticket_list',
                    'venue',
                    'primary_registration_details',
                    'primary_registration_list',
                    'event_author',
                ),
                'required' => array('[EVENT_LIST]'),
            ),
            'ticket_list' => array(
                'shortcodes' => array(
                    'attendee_list',
                    'ticket',
                    'datetime_list',
                    'primary_registration_details',
                    'line_item_list',
                    'venue',
                ),
                'required' => array('[TICKET_LIST]'),
            ),
            'ticket_line_item_no_pms' => array(
                'shortcodes' => array('line_item', 'ticket'),
                'required' => array('[TICKET_LINE_ITEM_LIST]'),
            ),
            'ticket_line_item_pms' => array(
                'shortcodes' => array('line_item', 'ticket', 'line_item_list'),
                'required' => array('[TICKET_LINE_ITEM_LIST]'),
            ),
            'price_modifier_line_item_list' => array(
                'shortcodes' => array('line_item'),
                'required' => array('[PRICE_MODIFIER_LINE_ITEM_LIST]'),
            ),
            'datetime_list' => array(
                'shortcodes' => array('datetime'),
                'required' => array('[DATETIME_LIST]'),
            ),
            'attendee_list' => array(
                'shortcodes' => array('attendee'),
                'required' => array('[ATTENDEE_LIST]'),
            ),
            'tax_line_item_list' => array(
                'shortcodes' => array('line_item'),
                'required' => array('[TAX_LINE_ITEM_LIST]'),
            ),
            'additional_line_item_list' => array(
                'shortcodes' => array('line_item'),
                'required' => array('[ADDITIONAL_LINE_ITEM_LIST]'),
            ),
            'payment_list' => array(
                'shortcodes' => array('payment'),
                'required' => array('[PAYMENT_LIST_*]'),
            ),
        );
    }


    /**
     * This is a method called from EE_messages when this messenger is a generating messenger and the sending messenger
     * is a different messenger.  Child messengers can set hooks for the sending messenger to callback on if necessary
     * (i.e. swap out css files or something else).
     *
     * @since 4.5.0
     * @param string $sending_messenger_name the name of the sending messenger so we only set the hooks needed.
     * @return void
     */
    public function do_secondary_messenger_hooks($sending_messenger_name)
    {
        if ($sending_messenger_name = 'pdf') {
            add_filter('EE_messenger__get_variation__variation', array($this, 'add_html_css'), 10, 8);
        }
    }


    /**
     * @param                            $variation_path
     * @param \EE_Messages_Template_Pack $template_pack
     * @param                            $messenger_name
     * @param                            $message_type_name
     * @param                            $url
     * @param                            $type
     * @param                            $variation
     * @param                            $skip_filters
     * @return string
     */
    public function add_html_css(
        $variation_path,
        EE_Messages_Template_Pack $template_pack,
        $messenger_name,
        $message_type_name,
        $url,
        $type,
        $variation,
        $skip_filters
    )
    {
        $variation = $template_pack->get_variation(
            $this->name,
            $message_type_name,
            $type,
            $variation,
            $url,
            '.css',
            $skip_filters
        );
        return $variation;
    }


    /**
     * Takes care of enqueuing any necessary scripts or styles for the page.  A do_action() so message types using this
     * messenger can add their own js.
     *
     * @return void.
     */
    public function enqueue_scripts_styles()
    {
        parent::enqueue_scripts_styles();
        do_action('AHEE__EE_Html_messenger__enqueue_scripts_styles');
    }


    /**
     * _set_template_fields
     * This sets up the fields that a messenger requires for the message to go out.
     *
     * @access  protected
     * @return void
     */
    protected function _set_template_fields()
    {
        // any extra template fields that are NOT used by the messenger
        // but will get used by a messenger field for shortcode replacement
        // get added to the 'extra' key in an associated array
        // indexed by the messenger field they relate to.
        // This is important for the Messages_admin to know what fields to display to the user.
        // Also, notice that the "values" are equal to the field type
        // that messages admin will use to know what kind of field to display.
        // The values ALSO have one index labeled "shortcode".
        // The values in that array indicate which ACTUAL SHORTCODE (i.e. [SHORTCODE])
        // is required in order for this extra field to be displayed.
        //  If the required shortcode isn't part of the shortcodes array
        // then the field is not needed and will not be displayed/parsed.
        $this->_template_fields = array(
            'subject' => array(
                'input' => 'text',
                'label' => __('Page Title', 'event_espresso'),
                'type' => 'string',
                'required' => true,
                'validation' => true,
                'css_class' => 'large-text',
                'format' => '%s',
            ),
            'content' => '',
            //left empty b/c it is in the "extra array" but messenger still needs needs to know this is a field.
            'extra' => array(
                'content' => array(
                    'main' => array(
                        'input' => 'wp_editor',
                        'label' => __('Main Content', 'event_espresso'),
                        'type' => 'string',
                        'required' => true,
                        'validation' => true,
                        'format' => '%s',
                        'rows' => '15',
                    ),
                    'event_list' => array(
                        'input' => 'wp_editor',
                        'label' => '[EVENT_LIST]',
                        'type' => 'string',
                        'required' => true,
                        'validation' => true,
                        'format' => '%s',
                        'rows' => '15',
                        'shortcodes_required' => array('[EVENT_LIST]'),
                    ),
                    'ticket_list' => array(
                        'input' => 'textarea',
                        'label' => '[TICKET_LIST]',
                        'type' => 'string',
                        'required' => true,
                        'validation' => true,
                        'format' => '%s',
                        'css_class' => 'large-text',
                        'rows' => '10',
                        'shortcodes_required' => array('[TICKET_LIST]'),
                    ),
                    'ticket_line_item_no_pms' => array(
                        'input' => 'textarea',
                        'label' => '[TICKET_LINE_ITEM_LIST] <br>' . __(
                                'Ticket Line Item List with no Price Modifiers',
                                'event_espresso'
                            ),
                        'type' => 'string',
                        'required' => false,
                        'validation' => true,
                        'format' => '%s',
                        'css_class' => 'large-text',
                        'rows' => '5',
                        'shortcodes_required' => array('[TICKET_LINE_ITEM_LIST]'),
                    ),
                    'ticket_line_item_pms' => array(
                        'input' => 'textarea',
                        'label' => '[TICKET_LINE_ITEM_LIST] <br>' . __(
                                'Ticket Line Item List with Price Modifiers',
                                'event_espresso'
                            ),
                        'type' => 'string',
                        'required' => false,
                        'validation' => true,
                        'format' => '%s',
                        'css_class' => 'large-text',
                        'rows' => '5',
                        'shortcodes_required' => array('[TICKET_LINE_ITEM_LIST]'),
                    ),
                    'price_modifier_line_item_list' => array(
                        'input' => 'textarea',
                        'label' => '[PRICE_MODIFIER_LINE_ITEM_LIST]',
                        'type' => 'string',
                        'required' => false,
                        'validation' => true,
                        'format' => '%s',
                        'css_class' => 'large-text',
                        'rows' => '5',
                        'shortcodes_required' => array('[PRICE_MODIFIER_LINE_ITEM_LIST]'),
                    ),
                    'datetime_list' => array(
                        'input' => 'textarea',
                        'label' => '[DATETIME_LIST]',
                        'type' => 'string',
                        'required' => true,
                        'validation' => true,
                        'format' => '%s',
                        'css_class' => 'large-text',
                        'rows' => '5',
                        'shortcodes_required' => array('[DATETIME_LIST]'),
                    ),
                    'attendee_list' => array(
                        'input' => 'textarea',
                        'label' => '[ATTENDEE_LIST]',
                        'type' => 'string',
                        'required' => true,
                        'validation' => true,
                        'format' => '%s',
                        'css_class' => 'large-text',
                        'rows' => '5',
                        'shortcodes_required' => array('[ATTENDEE_LIST]'),
                    ),
                    'tax_line_item_list' => array(
                        'input' => 'textarea',
                        'label' => '[TAX_LINE_ITEM_LIST]',
                        'type' => 'string',
                        'required' => false,
                        'validation' => true,
                        'format' => '%s',
                        'css_class' => 'large-text',
                        'rows' => '5',
                        'shortcodes_required' => array('[TAX_LINE_ITEM_LIST]'),
                    ),
                    'additional_line_item_list' => array(
                        'input' => 'textarea',
                        'label' => '[ADDITIONAL_LINE_ITEM_LIST]',
                        'type' => 'string',
                        'required' => false,
                        'validation' => true,
                        'format' => '%s',
                        'css_class' => 'large-text',
                        'rows' => '5',
                        'shortcodes_required' => array('[ADDITIONAL_LINE_ITEM_LIST]'),
                    ),
                    'payment_list' => array(
                        'input' => 'textarea',
                        'label' => '[PAYMENT_LIST]',
                        'type' => 'string',
                        'required' => true,
                        'validation' => true,
                        'format' => '%s',
                        'css_class' => 'large-text',
                        'rows' => '5',
                        'shortcodes_required' => array('[PAYMENT_LIST_*]'),
                    ),
                ),
            ),
        );
    }


    /**
     * @see   definition of this method in parent
     * @since 4.5.0
     */
    protected function _set_default_message_types()
    {
        $this->_default_message_types = array('receipt', 'invoice');
    }


    /**
     * @see   definition of this method in parent
     * @since 4.5.0
     */
    protected function _set_valid_message_types()
    {
        $this->_valid_message_types = array('receipt', 'invoice');
    }


    /**
     * Displays the message in the browser.
     *
     * @since 4.5.0
     * @return string.
     */
    protected function _send_message()
    {
        $this->_template_args = array(
            'page_title' => html_entity_decode(stripslashes($this->_subject), ENT_QUOTES, "UTF-8"),
            'base_css' => $this->get_variation(
                $this->_tmp_pack,
                $this->_incoming_message_type->name,
                true,
                'base',
                $this->_variation
            ),
            'print_css' => $this->get_variation(
                $this->_tmp_pack,
                $this->_incoming_message_type->name,
                true,
                'print',
                $this->_variation
            ),
            'main_css' => $this->get_variation(
                $this->_tmp_pack,
                $this->_incoming_message_type->name,
                true,
                'main',
                $this->_variation
            ),
            'main_body' => wpautop(
                stripslashes_deep(
                    html_entity_decode(
                        apply_filters(
                            'FHEE__EE_Html_messenger___send_message__main_body',
                            $this->_content,
                            $this->_content,
                            $this->_incoming_message_type
                        ),
                        ENT_QUOTES,
                        "UTF-8"
                    )
                )
            ),
        );
        $this->_deregister_wp_hooks();
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts_styles'));
        echo $this->_get_main_template();
        exit();
    }


    /**
     * The purpose of this function is to de register all actions hooked into wp_head and wp_footer so that it doesn't
     * interfere with our templates.  If users want to add any custom styles or scripts they must use the
     * AHEE__EE_Html_messenger__enqueue_scripts_styles hook.
     *
     * @since 4.5.0
     * @return void
     */
    protected function _deregister_wp_hooks()
    {
        remove_all_actions('wp_head');
        remove_all_actions('wp_footer');
        remove_all_actions('wp_print_footer_scripts');
        remove_all_actions('wp_enqueue_scripts');
        global $wp_scripts, $wp_styles;
        $wp_scripts = $wp_styles = array();
        //just add back in wp_enqueue_scripts and wp_print_footer_scripts cause that's all we want to load.
        add_action('wp_footer', 'wp_print_footer_scripts');
        add_action('wp_print_footer_scripts', '_wp_footer_scripts');
        add_action('wp_head', 'wp_enqueue_scripts');
    }


    /**
     * Overwrite parent _get_main_template for display_html purposes.
     *
     * @since  4.5.0
     * @param bool $preview
     * @return string
     */
    protected function _get_main_template($preview = false)
    {
        $wrapper_template = $this->_tmp_pack->get_wrapper($this->name, 'main');
        //include message type as a template arg
        $this->_template_args['message_type'] = $this->_incoming_message_type;
        return EEH_Template::display_template($wrapper_template, $this->_template_args, true);
    }


    /**
     * @return string
     */
    protected function _preview()
    {
        return $this->_send_message();
    }


    protected function _set_admin_settings_fields()
    {
    }


    /**
     * add the "powered by EE" credit link to the HTML receipt and invoice
     *
     * @param string $content
     * @param string $content_again
     * @param \EE_message_type $incoming_message_type
     * @return string
     */
    public function add_powered_by_credit_link_to_receipt_and_invoice(
        $content = '',
        $content_again = '',
        EE_message_type $incoming_message_type
    )
    {
        if (
            ($incoming_message_type->name === 'invoice' || $incoming_message_type->name === 'receipt')
            && apply_filters('FHEE_EE_Html_messenger__add_powered_by_credit_link_to_receipt_and_invoice', true)
        ) {
            $content .= \EEH_Template::powered_by_event_espresso(
                    'aln-cntr',
                    '',
                    array('utm_content' => 'messages_system')
                )
                . EEH_HTML::div(EEH_HTML::p('&nbsp;'));
        }
        return $content;
    }

}
