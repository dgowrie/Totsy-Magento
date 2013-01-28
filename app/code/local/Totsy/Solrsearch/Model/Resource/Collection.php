<?php 

class Totsy_Solrsearch_Model_Resource_Collection extends Enterprise_Search_Model_Resource_Collection{
	public function getSize()
    {
        if (is_null($this->_totalRecords)) {
            list($query, $params) = $this->_prepareBaseParams();
            $params['limit'] = 1;

            $helper = Mage::helper('enterprise_search');
            $searchSuggestionsEnabled = ($this->_searchQueryParams != $this->_generalDefaultQuery
                    && $helper->getSolrConfigData('server_suggestion_enabled'));
            if ($searchSuggestionsEnabled) {
                $params['solr_params']['spellcheck'] = 'true';
                $searchSuggestionsCount = (int) $helper->getSolrConfigData('server_suggestion_count');
                if ($searchSuggestionsCount < 1) {
                    $searchSuggestionsCount = 1;
                }
                $params['solr_params']['spellcheck.count']  = $searchSuggestionsCount;
                $params['spellcheck_result_counts']         = (bool) $helper->getSolrConfigData(
                    'server_suggestion_count_results_enabled');
            }

            $this->_addDateFilter($params);

            $result = $this->_engine->getIdsByQuery($query, $params);
            if ($searchSuggestionsEnabled) {
                $this->_suggestionsData = $result['suggestions_data'];
            }

            $this->_totalRecords = $this->_engine->getLastNumFound();
        }

        return $this->_totalRecords;
    }


    /**
     * Load faceted data if not loaded
     *
     * @return Enterprise_Search_Model_Resource_Collection
     */
    public function loadFacetedData()
    {
        if (empty($this->_facetedConditions)) {
            $this->_facetedData = array();
            return $this;
        }

        list($query, $params) = $this->_prepareBaseParams();
        $params['solr_params']['facet'] = 'on';
        $params['facet'] = $this->_facetedConditions;

		$this->_addDateFilter($params);

        $result = $this->_engine->getResultForRequest($query, $params);
        $this->_facetedData = $result['faceted_data'];
        $this->_facetedDataIsLoaded = true;

        return $this;
    }

    /**
     * Search documents by query
     * Set found ids and number of found results
     *
     * @return Enterprise_Search_Model_Resource_Collection
     */
    protected function _beforeLoad()
    {
        $ids = array();
        if ($this->_engine) {
            list($query, $params) = $this->_prepareBaseParams();

            if ($this->_sortBy) {
                $params['sort_by'] = $this->_sortBy;
            }
            if ($this->_pageSize !== false) {
                $page              = ($this->_curPage  > 0) ? (int) $this->_curPage  : 1;
                $rowCount          = ($this->_pageSize > 0) ? (int) $this->_pageSize : 1;
                $params['offset']  = $rowCount * ($page - 1);
                $params['limit']   = $rowCount;
            }

            $needToLoadFacetedData = (!$this->_facetedDataIsLoaded && !empty($this->_facetedConditions));
            if ($needToLoadFacetedData) {
                $params['solr_params']['facet'] = 'on';
                $params['facet'] = $this->_facetedConditions;
            }
            
            $this->_addDateFilter($params);
            
            $result = $this->_engine->getIdsByQuery($query, $params);
            $ids    = (array) $result['ids'];

            if ($needToLoadFacetedData) {
                $this->_facetedData = $result['faceted_data'];
            }
        }

        $this->_searchedEntityIds = &$ids;
        $this->getSelect()->where('e.entity_id IN (?)', $this->_searchedEntityIds);

        /**
         * To prevent limitations to the collection, because of new data logic.
         * On load collection will be limited by _pageSize and appropriate offset,
         * but third party search engine retrieves already limited ids set
         */
        $this->_storedPageSize = $this->_pageSize;
        $this->_pageSize = false;

        return parent::_beforeLoad();
    }

    protected function _addDateFilter(&$params,$both=true){
        if (empty($params['filters']['date_start']) && ($both==true || $both=='start') ){
            $params['filters']['date_start'] = array(
            	'from' => null,
            	'to' => 'NOW'
            );
        }
        if (empty($params['filters']['date_end']) && ($both==true || $both=='end')){
            $params['filters']['date_end'] = array(
            	'from' => 'NOW',
            	'to' => null
            );
        }
    }
}

