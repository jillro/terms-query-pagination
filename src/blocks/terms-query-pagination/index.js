import {registerBlockType} from '@wordpress/blocks';
import TermsQueryPaginationEdit from './edit';
import metadata from './block.json';
import {queryPagination as icon} from '@wordpress/icons';
import save from "./save";

registerBlockType(metadata.name, {
    edit: TermsQueryPaginationEdit,
    save,
    icon
});
