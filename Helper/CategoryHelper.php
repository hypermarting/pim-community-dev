<?php
namespace Pim\Bundle\ProductBundle\Helper;

use \RecursiveArrayIterator;

use Doctrine\Common\Collections\Collection;

use Pim\Bundle\ProductBundle\Entity\Category;

/**
 * Category helper
 *
 * @author    Romain Monceau <romain@akeneo.com>
 * @copyright 2013 Akeneo SAS (http://www.akeneo.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 *
 */
class CategoryHelper
{
    /**
     * Format in array category trees. The tree where is the select category
     * will be selected (attribute selected at "true" in the response)
     *
     * @param array    $trees
     * @param Category $selectCategory
     *
     * @return array
     * @static
     */
    public static function treesResponse($trees, Category $selectCategory = null)
    {
        $return = array();
        $selectedTreeId = -1;

        if ($selectCategory != null) {
            $selectedTreeId = $selectCategory->getRoot();
        }

        foreach ($trees as $i => $tree) {
            $selectedTree = false;

            if (($selectedTreeId == -1) && ($i == 0)
                || ($tree->getId() == $selectedTreeId) ) {
                $selectedTree = true;
            }

            $return[] = array(
                'id' => $tree->getId(),
                'title'  => $tree->getTitle(),
                'selected' => $selectedTree ? "true" : "false"
            );
        }

        return $return;
    }

    /**
     * Format categories list into simple array with data formatted
     * for JStree json_data plugin.
     *
     * @param array $categories
     *
     * @return array
     * @static
     */
    public static function childrenResponse($categories)
    {
        $result = array();

        foreach ($categories as $category) {
            $result[] = array(
                'attr' => array(
                    'id' => 'node_'. $category->getId()
                ),
                'data'  => $category->getTitle() .' ('. $category->getProductsCount() .')',
                'state' => static::getState($category)
            );
        }

        return $result;
    }

    /**
     * Format categories tree into multi-dimensional arrays with attributes
     * needed by JStree json_data plugin.
     *
     * Optionnaly can generate a selected state for the provided selectCategory
     *
     * @param array $categories
     * @param Category $selectCategory
     *
     * @return array
     * @static
     */
    public static function childrenTreeResponse($categories, Category $selectCategory = null)
    {
        $return = static::formatCategory($categories, $selectCategory);

        return $return;
    }

    /**
     * Format a node with its children to the format expected by jstree
     *
     * @see http://www.jstree.com/documentation/json_data
     *
     * @param array $categories
     * @param Category $selectCategory
     *
     * @return array
     * @static
     */
    protected static function formatCategory(array $categories, Category $selectCategory = null)
    {
        $result = array();

        foreach ($categories as $category) {
            $state = 'leaf';
            if (!is_object($category['item'])) {
                print_r($category['item']);
            }

            if (count($category['__children']) > 0) {
                $state = 'open';
            } else {
                if ($category['item']->hasChildren()) {
                    $state = 'closed';
                }
            }

            if (($selectCategory != null) &&
                ($category['item']->getId() == $selectCategory->getId())) {
                $state .= ' toselect';
            }

            $result[] = array(
                'attr' => array(
                    'id' => 'node_'. $category['item']->getId()
                ),
                'data'  => $category['item']->getTitle(),
                'state' => $state,
                'children' => static::formatCategory($category['__children'], $selectCategory)
            );
        }

        return $result;

    }

    /**
     * Return the state of the category (leaf if no children, closed otherwise)
     *
     * @param Category $category
     *
     * @return string
     * @static
     */
    protected static function getState(Category $category)
    {
        return $category->hasChildren() ? 'closed' : 'leaf';
    }

    /**
     * Format product list
     *
     * @param ArrayCollection $products
     *
     * @return array
     * @static
     */
    public static function productsResponse($products)
    {
        $return = array();

        foreach ($products as $product) {
            $return[] = array(
                'id' => $product->getId(),
                'name' => $product->getSku(),
                'description' => $product->getSku()
            );
        }

        return $return;
    }

    /**
     * Format path with a list of ids from tree to node
     *
     * @param ArrayCollection $categoryPath
     *
     * @return multitype:integer
     * @static
     */
    public static function pathResponse($categoryPath)
    {
        $return = array();

        foreach ($categoryPath as $category) {
            $return[] = $category->getId();
        }

        return $return;
    }

    public static function listCategoriesResponse(array $categories, Collection $selectedCategories = null)
    {
        $selectedIds = array();

        foreach($selectedCategories as $selectedCategory) {
            $selectedIds[] = $selectedCategory->getId();
        }

        return static::formatCategoryAndCount($categories, $selectedIds, true);
    }

    /**
     * Format a node with its children to the format expected by jstree.
     * If count is true, the state will contain a count attribute representing
     * the number of selected children
     *
     * @see http://www.jstree.com/documentation/json_data
     *
     * @param array    $categories
     * @param array    $selectedCategoriesIds
     * @param boolean  $count
     *
     * @return array
     * @static
     */
    protected static function formatCategoryAndCount(array $categories, $selectedIds = null, $count = false)
    {
        $result = array();

        foreach($categories as $category) {
            $state = 'leaf';

            if (count($category['__children']) > 0) {
                $state = 'open';
            } else {
                if ($category['item']->hasChildren()) {
                    $state = 'closed';
                }
            }

            if (in_array($category['item']->getId(), $selectedIds)) {
                $state .= ' jstree-checked';
            }

            $children = static::formatCategoryAndCount($category['__children'], $selectedIds, $count);

            $selectedChildrenCount = 0;

            foreach ($children as $child) {
                $selectedChildrenCount += $child['selectedChildrenCount'];
                if (preg_match('/checked/', $child['state'])) {
                    $selectedChildrenCount ++;
                }
            }
           
            $title = $category['item']->getTitle();

            if ($selectedChildrenCount > 0) {
                $title = '<strong>'.$title.'</strong>';
            }


            $result[] = array(
                'attr' => array(
                    'id' => 'node_'. $category['item']->getId()
                ),
                'data'  => $title,
                'state' => $state,
                'children' => $children,
                'selectedChildrenCount' => $selectedChildrenCount
            );
        }

        return $result;

    }
}
