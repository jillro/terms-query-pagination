import {registerBlockType} from '@wordpress/blocks';
import Edit from './edit';
import metadata from './block.json';
import {queryPaginationNext as icon} from '@wordpress/icons';

registerBlockType(metadata.name, {
    edit: Edit,
    icon
});
