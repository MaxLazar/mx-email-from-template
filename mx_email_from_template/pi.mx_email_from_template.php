<?php if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

/**
 * MX Email From Template Plugin class
 *
 * @package        mx_email_from_template
 * @author         Max Lazar <max@eec.ms>
 * @link           http://eec.ms/
 * @license        http://opensource.org/licenses/MIT
 */
class Mx_email_from_template
{

    // --------------------------------------------------------------------
    // PROPERTIES
    // --------------------------------------------------------------------


    /**
     * [$_cache_path description]
     *
     * @var boolean
     */
    private $_cache_path = false;

    /**
     * [$files description]
     *
     * @var boolean
     */
    private $files = array();

    /**
     * [$files_to_unlink description]
     *
     * @var boolean
     */
    private $files_to_unlink = array();

    /**
     * Package name
     *
     * @var        string
     * @access     protected
     */
    protected $package;

    /**
     * Plugin return data
     *
     * @var        string
     */
    public $return_data;


    /**
     * Plugin return data
     *
     * @var        string
     */
    public $settings = array(
        'to'                      => '',
        'cc'                      => '',
        'bcc'                     => '',
        'from'                    => '',
        'subject'                 => '',
        'decode_subject_entities' => '',
        'decode_message_entities' => '',
        'mailtype'                => 'html',
        'alt_message'             => false,
        'attachments'             => 'false',
        'security_key'            => '',
        'httpagent'               => '',
        'ip'                      => '',
        'uri_string'              => '',
        'redirect'                => ''
    );


    /**
     * Site id shortcut
     *
     * @var        int
     * @access     protected
     */
    protected $site_id;

    // --------------------------------------------------------------------
    // METHODS
    // --------------------------------------------------------------------

    /**
     * Constructor
     *
     * @access     public
     * @return     string
     */
    public function __construct()
    {
        if (!isset(ee()->config->item('mx_email_from_template')['paths'])) {
            ee()->config->set_item('mx_email_from_template', array('paths' => FCPATH));
        }

        $this->_cache_path = (!$this->_cache_path) ? str_replace(
                '\\',
                '/',
                PATH_CACHE
            ) . '/email_from_template' : false;

        $this->package                = basename(__DIR__);
        $this->info                   = ee('App')->get($this->package);
        $this->settings['from']       = $this->settings['to'] = ee()->config->item('webmaster_email');
        $this->settings['subject']    = "Email from: " . ee()->uri->uri_string();
        $this->settings['ip']         = ee()->input->ip_address();
        $this->settings['httpagent']  = ee()->input->user_agent();
        $this->settings['uri_string'] = ee()->uri->uri_string();
        $this->return_data            = $this->email();

        return $this->return_data;
    }

    /**
     * Displays the nice date
     *
     * @access     public
     * @return     string
     */
    public function email()
    {
        // -------------------------------------------
        // Get parameters
        // -------------------------------------------

        $LD      = '\{';
        $RD      = '\}';
        $SLASH   = '\/';
        $tagdata = ee()->TMPL->tagdata;

        $errors = array();

        ee()->load->helper('file');

        foreach ($this->settings as $key => $value) {
            $this->settings[$key] = ee('Security/XSS')->clean(ee()->TMPL->fetch_param($key, $value));
        }

        $redirect = ee('Security/XSS')->clean(ee()->TMPL->fetch_param($key, false));
        $echo     = ee('Security/XSS')->clean(ee()->TMPL->fetch_param('echo', false));
        $echo     = $echo == 'on' ? true : false;

        $variables[] = $this->settings;
        $tagdata     = ee()->TMPL->parse_variables($tagdata, $variables);

        $this->settings['subject'] = ee()->TMPL->parse_globals($this->settings['subject']);
        $tagdata                   = ee()->TMPL->parse_globals($tagdata);

        $subject = ee()->TMPL->parse_globals($this->settings['subject']);
        $tagdata = ee()->TMPL->parse_globals($tagdata);

        $tagdata = $this->create_files($tagdata);
        $message = $this->process_files($tagdata);

        ee()->load->helper('text');
        ee()->load->library('email');

        if ($this->settings['decode_message_entities'] != 'no') {
            $message = $this->settings['decode_message_entities'] ? entities_to_ascii($message) : $message;
        }

        if ($this->settings['decode_subject_entities'] != 'no') {
            $subject = $this->settings['decode_subject_entities'] ? entities_to_ascii($subject) : $subject;
        }

        ee()->email->wordwrap = true;
        ee()->email->mailtype = $this->settings['mailtype'];
        ee()->email->from($this->settings['from']);
        ee()->email->to($this->settings['to']);
        ee()->email->cc($this->settings['cc']);
        ee()->email->bcc($this->settings['bcc']);
        ee()->email->subject($subject);

        ee()->email->message($message);

        if (count($this->files) > 0) {
            $this->log_debug_message('Adding attachments...', 'start');

            foreach ($this->files as $attachment_path) {
                $attachment_path = reduce_double_slashes(ee()->config->item('mx_email_from_template')['paths'] . $attachment_path);
                if (is_file($attachment_path)) {
                    $this->log_debug_message('Attachment: ', $attachment_path);
                    ee()->email->attach($attachment_path);
                } else {
                    $this->log_debug_message('File not exist: ', $attachment_path);
                }
            }
        }

        if (!ee()->email->Send()) {
            $errors[] = ee()->email->print_debugger();
            ee()->email->clear(true);
        }

        $this->log_debug_message('Send email', 'done');

        // delete temp files
        $this->unlink_files($this->files_to_unlink);

        if ($redirect) {
            ee()->functions->redirect($redirect);
            die();
        }

        if ($echo) {
            $this->return_data = $message;
        }

        return $this->return_data;
    }

    /**
     * [create_files description]
     * @param  [type] $data       [description]
     * @param  [type] $parameters [description]
     * @return [type]             [description]
     */
    protected function create_files($tagdata)
    {
        $variable_csv = "csv:file";
        if (preg_match(
            "/" . LD . $variable_csv . ".*?" . RD . "(.*?)" . LD . '\/' . $variable_csv . RD . "/s",
            $tagdata,
            $csv
        )) {
            $this->log_debug_message('Generate CSV file', 'start');

            $file_name = $this->_cache_path . '/' . time() . ".csv";

            if (!write_file($file_name, $csv[1])) {
                $this->log_debug_message('Generate CSV file', 'failed');
            } else {
                $this->files[]           = $file_name;
                $this->files_to_unlink[] = $file_name;
            }

            if (!is_dir($this->_cache_path)) {
                mkdir($this->_cache_path . "", 0777, true);
            }

            $tagdata = str_replace($csv[0], "", $tagdata);
        }

        return $tagdata;
    }

    /**
     * [process_files description]
     * @param  [type] $data [description]
     * @return [type]       [description]
     */
    protected function process_files($tagdata)
    {
        $variable_files = "files";
        if (preg_match(
            "/" . LD . $variable_files . ".*?" . RD . "(.*?)" . LD . '\/' . $variable_files . RD . "/s",
            $tagdata,
            $file_list
        )) {
            $filenames = explode("]", str_replace(array('&#47;', '[', "\n"), array('/', '', ''), $file_list[1]));
            $tagdata   = str_replace($file_list[0], "", $tagdata);
            foreach ($filenames as $key => $value) {
                $this->files[] = trim($value);
            }
        }

        return $tagdata;
    }

    /**
     * [email_split description] Thanks to https://stackoverflow.com/questions/16685416/split-full-email-addresses-into-name-and-email
     * @param  [type] $str [description]
     * @return [type]      [description]
     */
    public function email_split($str)
    {
        $str .= " ";

        $re = '/(?:,\s*)?(.*?)\s*(?|<([^>]*)>|\[([^][]*)]|(\S+@\S+))/';
        preg_match_all($re, $str, $m, PREG_SET_ORDER, 0);

        $name  = (isset($m[0][1])) ? $m[0][1] : '';
        $email = (isset($m[0][2])) ? $m[0][2] : '';

        return array('name' => trim($name), 'email' => trim($email));
    }

    /**
     * [unlink_files description]
     *
     * @param [type]  $files [description]
     * @return [type]        [description]
     */
    protected function unlink_files($files)
    {
        foreach ($files as $key) {
            unlink($key);
        }
        return true;
    }

    /**
     * Simple method to log a debug message to the EE Debug console.
     *
     * @param string $method
     * @param string $message
     * @return void
     */
    protected function log_debug_message($method = '', $message = '')
    {
        ee()->TMPL->log_item("&nbsp;&nbsp;***&nbsp;&nbsp;" . $this->package . " - $method debug: " . $message);
    }
}
// END CLASS

/* End of file pi.mx_email_from_template.php */
