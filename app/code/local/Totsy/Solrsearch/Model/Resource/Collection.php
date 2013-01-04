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

