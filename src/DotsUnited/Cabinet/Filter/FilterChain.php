<?php

/*
 * This file is part of Cabinet.
 *
 * (c) 2011 Jan Sorgalla <jan.sorgalla@dotsunited.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace DotsUnited\Cabinet\Filter;

/**
 * DotsUnited\Cabinet\Filter\FilterChain
 *
 * @author  Jan Sorgalla <jan.sorgalla@dotsunited.de>
 * @version @package_version@
 */
class FilterChain implements FilterInterface
{
    const CHAIN_APPEND = 'append';
    const CHAIN_PREPEND = 'prepend';

    /**
     * The filter chain.
     *
     * @var array
     */
    protected $filters = array();

    /**
     * Adds a filter to the chain.
     *
     * @param Filter $filter
     * @param string $placement
     * @return FilterChain
     */
    public function addFilter(FilterInterface $filter, $placement = self::CHAIN_APPEND)
    {
        if ($placement == self::CHAIN_PREPEND) {
            array_unshift($this->filters, $filter);
        } else {
            $this->filters[] = $filter;
        }
        return $this;
    }

    /**
     * Add a filter to the end of the chain.
     *
     * @param Filter $filter
     * @return FilterChain
     */
    public function appendFilter(FilterInterface $filter)
    {
        return $this->addFilter($filter, self::CHAIN_APPEND);
    }

    /**
     * Add a filter to the start of the chain.
     *
     * @param Filter $filter
     * @return FilterChain
     */
    public function prependFilter(FilterInterface $filter)
    {
        return $this->addFilter($filter, self::CHAIN_PREPEND);
    }

    /**
     * Get all the filters.
     *
     * @return Filter[]
     */
    public function getFilters()
    {
        return $this->filters;
    }

    /**
     * Reset all the filters.
     *
     * @return FilterChain
     */
    public function resetFilters()
    {
        $this->filters = array();
        return $this;
    }

    /**
     * Returns $value filtered through each filter in the chain.
     *
     * Filters are run in the order in which they were added to the chain (FIFO).
     *
     * @param mixed $value
     * @return mixed
     */
    public function filter($value)
    {
        $valueFiltered = $value;
        foreach ($this->filters as $filter) {
            $valueFiltered = $filter->filter($valueFiltered);
        }
        return $valueFiltered;
    }
}
