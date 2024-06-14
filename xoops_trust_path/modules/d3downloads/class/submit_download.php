<?php

// for submit_data

if( ! class_exists( 'submit_download' ) )
{
	include_once dirname( dirname(__FILE__) ).'/class/mydownload.php' ;
	require_once dirname( dirname(__FILE__) ).'/class/d3downloads.textsanitizer.php' ;

	class submit_download extends MyDownload
	{
		public $db;
		public $table;
		public $lid ;
		public $cid ;
		public $title ;
		public $url ;
		public $filename ;
		public $ext ;
		public $file2 ;
		public $filename2 ;
		public $ext2 ;
		public $homepage ;
		public $homepagetitle ;
		public $version ;
		public $size ;
		public $platform ;
		public $license ;
		public $logourl ;
		public $description ;
		public $html ;
		public $smiley ;
		public $br ;
		public $xcode ;
		public $filters ;
		public $extra ;
		public $submitter ;
		public $date ;
		public $expired ;
		public $hits ;
		public $rating ;
		public $votes ;
		public $visible ;
		public $cancomment ;
		public $comments ;
		public $downdata=array();

		public function submit_download( $mydirname )
		{
			include_once dirname( dirname(__FILE__) ).'/include/mytable.php' ;

			$this->db =& Database::getInstance();
			$this->myts =& d3downloadsTextSanitizer::sGetInstance() ;
			$this->table = $this->db->prefix( "{$mydirname}_downloads" ) ;
			$this->mydirname = $mydirname ;
			$this->mod_url = XOOPS_URL.'/modules/'.$mydirname ;
			$columns = implode( ',' , $GLOBALS['d3download_tables']['downloads'] ) ;
			$this->columns = $columns ;
			$module_handler =& xoops_gethandler('module');
			$config_handler =& xoops_gethandler('config');
			$module =& $module_handler->getByDirname( $mydirname );
			$mod_config =& $config_handler->getConfigsByCat( 0, $module->getVar( 'mid' ) );
			$this->mod_config = $mod_config ;
		}

		public function get_downdata_for_submit( $lid, $category )
		{
			$result = $this->db->query("SELECT  $this->columns  FROM ".$this->table."  WHERE lid='".$lid."'");
			if ( $this->db->getRowsNum( $result ) == 0 ) {
				return '';
			}
			$this->getData( $result ) ;
			$lid         = $this->return_lid() ;
			$cid         = $this->return_cid() ;
			$submitter   = $this->return_submitter() ;
			$url         = $this->return_url('Edit') ;
			$filename    = $this->return_filename('Edit') ;
			$ext         = $this->return_ext('Edit') ;
			$file_info   = $this->file_link_for_post( $lid, $cid, $this->return_url('Show'), $filename ) ;
			$file2       = $this->return_file2('Edit') ;
			$filename2   = $this->return_filename2('Edit') ;
			$ext2        = $this->return_ext2('Edit') ;
			$file_info2  = $this->file_link_for_post( $lid, $cid,$this->return_file2('Show'), $filename2, 1 ) ;
			$logourl     = $this->return_logourl('Edit') ;
			$title       = $this->return_title('Edit') ;
			$hits        = $this->return_hits() ;
			$totalrating = $this->return_rating() ;
			$totalvotes  = $this->return_votes() ;
			$comments    = $this->return_cancomment() ;

			$downdata = array(
				'lid'           => $lid ,
				'cid'           => $cid ,
				'category'      => $category ,
				'title'         => $title ,
				'url'           => $url ,
				'filename'      => $filename ,
				'ext'           => $ext ,
				'filelink'      => $file_info['filelink'] ,
				'filenamelink'  => $file_info['filenamelink'] ,
				'file2'         => $file2 ,
				'filename2'     => $filename2 ,
				'ext2'          => $ext2 ,
				'filelink2'     => $file_info2['filelink'] ,
				'filenamelink2' => $file_info2['filenamelink'] ,
				'homepage'      => $this->return_homepage('Edit') ,
				'homepagetitle' => $this->return_homepagetitle('Edit') ,
				'version'       => $this->return_version('Edit') ,
				'size'          => $this->return_size() ,
				'platform'      => $this->return_platform('Edit') ,
				'license'       => $this->return_license('Edit') ,
				'logourl'       => $logourl ,
				'shots_link'    => ( empty( $logourl ) ) ? '' : $this->shots_link_for_post( $cid, $logourl ) ,
				'description'   => $this->return_description('Edit') ,
				'submitter'     => $submitter ,
				'postname'      => $this->getlink_for_postname( $submitter ) ,
				'html'          => $this->return_html() ,
				'smiley'        => $this->return_smiley() ,
				'br'            => $this->return_br() ,
				'xcode'         => $this->return_xcode() ,
				'filters'       => $this->get_MyFilter( $this->filters ) ,
				'extra'         => $this->return_extra('Edit') ,
				'createable'    => $this->return_createable() ,
				'date'          => $this->return_date() ,
				'expired'       => $this->return_expired() ,
				'expiredable'   => $this->return_expiredable() ,
				'visible'       => $this->return_visible() ,
				'cancomment'    => $this->return_cancomment() ,
			) ;
			return array(
				'lid'         => $lid ,
				'cid'         => $cid ,
				'submitter'   => $submitter,
				'postname'    => $this->get_postname( $submitter ),
				'title'       => $title,
				'hits'        => $hits ,
				'totalrating' => $totalrating ,
				'totalvotes'  => $totalvotes ,
				'comments'    => $comments ,
				'downdata'    => $downdata ,
			) ;
		}
	}
}

?>