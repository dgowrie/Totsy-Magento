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

            $params['filters']['date_start'] = array(
            	'from' => null,
            	'to' => 'NOW'
            );

            $params['filters']['date_end'] = array(
            	'from' => 'NOW',
            	'to' => null
            );

            $result = $this->_engine->getIdsByQuery($query, $params);
            if ($searchSuggestionsEnabled) {
                $this->_suggestionsData = $result['suggestions_data'];
            }

            $this->_totalRecords = $this->_engine->getLastNumFound();
        }

        return $this->_totalRecords;
    }
}