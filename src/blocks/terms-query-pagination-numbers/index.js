import {registerBlockType} from '@wordpress/blocks';
import Edit from './edit';
import metadata from './block.json';
import {queryPaginationNumbers as icon} from '@wordpress/icons';

registerBlockType(metadata.name, {
    edit: Edit,
    icon
});
