<?php
/**
 * DokuWiki Plugin sqljson (Syntax Component)
 *
 * @license GPL 2 http://www.gnu.org/licenses/gpl-2.0.html
 * @author  George Pirogov <i1557@yandex.ru>
 * @author  Marie Mandrela <marie.h.mandrela@gmail.com>
 */

// must be run within Dokuwiki
if (!defined('DOKU_INC')) die();

class syntax_plugin_sqljson extends DokuWiki_Syntax_Plugin {

    public function getType() {
        return 'substition';
    }

    public function getSort() {
        return 666;
    }

    public function connectTo($mode)
    {
        $this->Lexer->addEntryPattern('<sqljson(?=.*?>)', $mode, 'plugin_sqljson');
    }

    public function postConnect()
    {
        $this->Lexer->addExitPattern('</sqljson>','plugin_sqljson');
    }

    /**
     * Handle matches of the sqljson syntax
     *
     * @param string          $match   The match of the syntax
     * @param int             $state   The state of the handler
     * @param int             $pos     The position in the document
     * @param Doku_Handler    $handler The handler
     * @return array Data for the renderer
     */
    public function handle($match, $state, $pos, Doku_Handler $handler)
    {
        switch ($state) {
            case DOKU_LEXER_ENTER:
            $data = array();
            return $data;
            break;
			
            case DOKU_LEXER_UNMATCHED:
            // will include everything from <sqljson ... to ... </sqljson >
            // e.g. ... [name] > [sqljson]
            list($attr, $content) = preg_split('/>/u',$match,2);
            return array('sqljson' => $content, 'variable' => trim($attr));
            break;

			//return array($this->syntax, trim($lang), trim($title), $content);
            
            case DOKU_LEXER_EXIT:
            $data = array();
            return $data;
            break;
		}       
		
        $data = array();
        return $data;
    }

    /**
     * Render xhtml output or metadata
     *
     * @param string         $mode      Renderer mode (supported modes: xhtml)
     * @param Doku_Renderer  $renderer  The renderer
     * @param array          $data      The data from the handler() function
     * @return bool If rendering was successful.
     */
    public function render($mode, Doku_Renderer $renderer, $data)
    {
        if ( $mode != 'xhtml' ) return false;

        if ( !empty( $data['sqljson'] ) )
        {
            // get the configuration parameters
            $host     = $this->getConf('Host');
            $DB       = $this->getConf('DB');
            $user     = $this->getConf('user');
            $password = $this->getConf('password');

            // get a query
            $querystring = $data['sqljson'];

            // connect to the database
            $link = mysqli_connect($host, $user, $password, $DB);
            mysqli_set_charset($link, "utf8");

            // connected
            if ( $link )
            {
                $result = mysqli_query($link, $querystring);
                if ( $result )
                {
                    // get the number of fields in the table
                    $fieldcount = mysqli_num_fields($result);
                    // get the number of rows in the table
                    $rowcount = mysqli_num_rows($result);

                    // open script tag
                    $renderer->doc .= "<script>";
                    
                    // open json 
                    if (strlen($data['variable']) > 0)
                    {
                        $renderer->doc .= "var ";
                        $renderer->doc .= $data['variable'];
                        $renderer->doc .= " = ["; 
                    } 
                    else 
                    {
                        $renderer->doc .= "var data = ["; 
                    }
                    
                    $header = array();
                    while ($fieldinfo = mysqli_fetch_field($result))
                    {
                        array_push($header, $fieldinfo->name);
                    }

                    // build the json entries
                    $j = 0;
                    while ($row = mysqli_fetch_row($result))
                    {
                          $renderer->doc .= "{ ";
                          
                          // construct a row
                          for ( $i = 0; $i < $fieldcount; $i++ ) 
                          {
                              if ( $i > 0 )
                              {
                                  $renderer->doc .= " , ";
                              }
                           
                              $renderer->doc .= "\"";
                              $renderer->doc .= $header[$i];
                              $renderer->doc .= "\":\"";
                              $renderer->doc .= $row[$i];
                              $renderer->doc .= "\"";
                          }
                          
                          $renderer->doc .= " }";
                          if ( $j < $rowcount - 1 )
                          {
                              $renderer->doc .= ",";
                          }
                          $j++;
                    }
                    // close json
                    $renderer->doc .= "];";
                    
                    // close script tag
                    $renderer->doc .= "</script>";
                }
                mysqli_close($link);
            }
        }
        return true;
    }
}

// vim:ts=4:sw=4:et:
