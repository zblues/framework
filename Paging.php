<?php namespace zblues/framework;

class Paging {
	/* 파라메타용 변수 */
	protected $mListNameHead; //리스트 프로그램명
  protected $mListNameTail; //리스트 프로그램명
  protected $mCurPageNum;		//현재 페이지 번호
	protected $mPageVar;			//페이지에 사용되는 변수명
	protected $mTotalRow;		  //글갯수
	protected $mPagePerBlock;	//출력 페이지수
	protected $mRowPerPage;		//출력 글 수
	protected $mPrevPage;			//[이전 페이지] text 또는 img tag
	protected $mNextPage;			//[다음 페이지] text 또는 img tag
	protected $mBlockPrevPage;//[이전 $mPagePerBlock 페이지] text 또는 img tag
	protected $mBlockNextPage;//[다음 $mPagePerBlock 페이지] text 또는 img tag
	protected $mFirstPage;		//[처음] 페이지 text 또는 img tag
	protected $mLastPage;			//[마지막] 페이지 text 또는 img tag
	protected $mUlCss;        //Pagination Css
  protected $mPageCss;		  //페이지 목록에 사용할 css
	protected $mCurPageCss;	  //현재 페이지에 사용할 css

	/* 내부사용 변수 */
	protected $mPageCount;		  //전체 페이지수
	protected $mTotalBlock;		  //전체 블럭수
	protected $mBlock;				  //현재 블럭수
	protected $mBlockFirstPage;	//한블럭의 첫 페이지번호
	protected $mBlockLastPage;	//한블럭의 마지막 페이지 번호
	//protected static $instance; //singleton용 인스턴스 변수

	/**
	* 싱글톤용 인스턴스 리턴
	* @param array $params
	*/
  public static function getInstance($reg)
  {
    static $instance = null;
    if($instance === null)
    {
      $instance = new static($reg);
    }
    return $instance;
  }
/*  
	public static function getInstance($params) {
		if(!isset(self::$instance)) {
			self::$instance = new self($params);
		}
		else {
			//이미 인스턴스가 생성되있을 경우 파라메터를 재적용(한페이지내에 다른 파라메타로 여려 페이징을 써야 할 경우)
			self::$instance->__construct($params);
		}

		return self::$instance;
	}
*/
	/**
	* 생성자 - 온션을 성정하고 기본적인 페이지,블럭수 등을 계산
	* @param array $params
	*/
	public function __construct($params) {
		if(!count($params)) {
			echo "[Paging Error : 파라미터가 설정되지 않았습니다.]";
			return;
		}

    $this->mListNameHead = isset($params['listNameHead']) ? $params['listNameHead'] : '/board/list?empty';
    $this->mListNameTail = isset($params['listNameTail']) ? $params['listNameTail'] : '';
    $this->mCurPageNum = isset($params['curPageNum']) ? $params['curPageNum'] : 1;
		$this->mPageVar = isset($params['pageVar']) ? $params['pageVar'] : 'p';
		$this->mTotalRow = isset($params['totalRow']) ? $params['totalRow'] : 0;
		$this->mPagePerBlock = isset($params['PagePerBlock']) ? $params['PagePerBlock'] : 10;
		$this->mRowPerPage = isset($params['RowPerPage']) ? $params['RowPerPage'] : 15;
		$this->mPrevPage = isset($params['prevPage']) ? $params['prevPage'] : '이전';
		$this->mNextPage = isset($params['nextPage']) ? $params['nextPage'] : '다음';
		$this->mBlockPrevPage = isset($params['BlockPrevPage']) ? $params['BlockPrevPage'] : '';
		$this->mBlockNextPage = isset($params['BlockNextPage']) ? $params['BlockNextPage'] : '';
		$this->mFirstPage = isset($params['firstPage']) ? $params['firstPage'] : '처음';
		$this->mLastPage = isset($params['lastPage']) ? $params['lastPage'] : '마지막';
    $this->mUlCss = isset($params['ulCss']) ? $params['ulCss'] : 'pagination';
		$this->mPageCss = isset($params['pageCss']) ? $params['pageCss'] : '';
		//$this->mCurPageCss = isset($params['curPageCss']) ? $params['curPageCss'] : 'active';
    $this->mCurPageCss = 'active';

		$this->mPageCount = ceil($this->mTotalRow/$this->mRowPerPage);
		$this->mTotalBlock = ceil($this->mPageCount/$this->mPagePerBlock);
		$this->mBlock = ceil($this->mCurPageNum/$this->mPagePerBlock);
		$this->mBlockFirstPage = ($this->mBlock-1)*$this->mPagePerBlock;
		$this->mBlockLastPage = $this->mTotalBlock<=$this->mBlock ? $this->mPageCount : $this->mBlock*$this->mPagePerBlock;
    
    //echo $this->mTotalRow . ' ' . $this->mRowPerPage . ' ' . $this->mPageCount . ' ' . $this->mTotalBlock . ' ' . $this->mBlock . ' ' . $this->mBlockFirstPage . ' ' . $this->mBlockLastPage . '<br>';
	}
  
  function __destruct()
  {
    //self::$instance = null;
  }

	/**
	* 현재 글번호를 리턴
	* @return integer
	*/
	public function getStartRowNum() {
		return ($this->mCurPageNum-1)*$this->mRowPerPage;
	}
  
  /**
	* 현재 글번호를 리턴
	* @return integer
	*/
	public function getRowNum() {
		return $this->mTotalRow-($this->mCurPageNum-1)*$this->mRowPerPage;
	}
  
  /**
	* 페이지 당 글 갯수(mRowPerPage) 리턴
	* @return integer
	*/
	public function getRowPerPage() {
		return $this->mRowPerPage;
	}
  
  /**
	* 전체 글 갯수(mTotalRow) 리턴
	* @return integer
	*/
	public function getTotalRow() {
		return $this->mTotalRow;
	}


	/**
	* 첫페이지 번호 링크를 리턴
	* @return string
	*/
	public function getFirstPage() {
		if(empty($this->mFirstPage) || $this->mCurPageNum == 1) return NULL;
		return '<li><a href="'.$this->mListNameHead.'1'.$this->mListNameTail.'">'.$this->mFirstPage.'</a></li>';
	}

	/**
	* 끝페이지 번호 링크를 리턴
	* @return string
	*/
	public function getLastPage() {
		if(empty($this->mLastPage) || $this->mCurPageNum == $this->mPageCount || $this->mPageCount==0) return NULL;
		return '<li><a href="'.$this->mListNameHead.$this->mPageCount.$this->mListNameTail.'">'.$this->mLastPage.'</a></li>';
	}

	/**
	* 이전블럭 링크를 리턴
	* @return string
	*/
	public function getBlockPrevPage() {
		if(empty($this->mBlockPrevPage) || $this->mBlock <= 1) return NULL;
		return '<li><a href="'.$this->mListNameHead.$this->mBlockFirstPage.$this->mListNameTail.'">'.$this->mBlockPrevPage.'</a></li>';
	}

	/**
	* 다음블럭 링크를 리턴
	* @return string
	*/
	public function getBlockNextPage() {
		if(empty($this->mBlockNextPage) || $this->mBlock >= $this->mTotalBlock) return NULL;
		return '<li><a href="'.$this->mListNameHead.($this->mBlockLastPage+1).$this->mListNameTail.'">'.$this->mBlockNextPage.'</a></li>';
	}

	/**
	* 이전 페이지 링크를 리턴
	* @return string
	*/
	public function getPrevPage() {
		if($this->mCurPageNum > 1)
			return '<li><a href="'.$this->mListNameHead.($this->mCurPageNum-1).$this->mListNameTail.'">'.$this->mPrevPage.'</a></li>';
		else
			return '<li><a>'.$this->mPrevPage.'</a></li>';
	}

	/**
	* 다음 페이지 링크를 리턴
	* @return string
	*/
	public function getNextPage() {
		if($this->mCurPageNum != $this->mPageCount && $this->mPageCount)
			return '<li><a href="'.$this->mListNameHead.($this->mCurPageNum+1).$this->mListNameTail.'">'.$this->mNextPage.'</a><li>';
		else
			return '<li><a>'.$this->mNextPage.'</a></li>';
	}

	/**
	* 페이지 목록 링크를 리턴
	* @return string
	*/
	public function getPageList() {
		$rtn = '';
		for($i=$this->mBlockFirstPage+1;$i<=$this->mBlockLastPage;$i++) {
			if($this->mCurPageNum == $i) {
				if(empty($this->mCurPageCss))
					$rtn .= '<li><a>'.$i.'</a></li>';
				else
					$rtn .= '<li class="'.$this->mCurPageCss.'"><a>'.$i.'</a></li>';
			} else {
				$rtn .= '<li><a href="'.$this->mListNameHead.$i.$this->mListNameTail.'">';
				if(empty($this->mPageCss)) 
					$rtn .= $i;
				else
					$rtn .= '<span class="'.$this->mPageCss.'">'.$i.'</span>';
        $rtn .= '</a></li>';
			}
		}
    if($rtn=='') $rtn = '<li class="'.$this->mCurPageCss.'"><a>1</a></li>';
#Util::msLog($rtn);
		//return '<li>'.$rtn.'</li>';
    return $rtn;
	}

	
  public function getPaging() {
    $str = '<ul class="'.$this->mUlCss.'">';
		$str .= $this->getFirstPage();
		//echo '&nbsp;&nbsp;';
		$str .= $this->getBlockPrevPage();
		//echo '&nbsp;&nbsp;';
		$str .= $this->getPrevPage();
		//echo '&nbsp;&nbsp;';
		$str .= $this->getPageList();
		//echo '&nbsp;&nbsp;';
		$str .= $this->getNextPage();
		//echo '&nbsp;&nbsp;';
		$str .= $this->getBlockNextPage();
		//echo '&nbsp;&nbsp;';
		$str .= $this->getLastPage();
    $str .= '</ul>';
    
    return $str;
	}
  /**
	* 기본 페이지를 프린트, 상속후 변경 가능
	*/
	public function printPaging() {
    echo $this->getPaging();
	}
  
  public function getCurrentPage()
  {
    return $this->mCurPageNum;
  }
  
  public function getMaxPage()
  {
    return $this->mPageCount;
  }
}

?>