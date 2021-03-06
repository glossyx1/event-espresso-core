/**
 * External dependencies
 */
import { isSchemaResponseOfModel } from '@eventespresso/validators';

/**
 * Internal dependencies
 */
import { receiveSchemaForModel, receiveFactoryForModel } from './actions';
import { getEndpoint, createEntityFactory, MODEL_PREFIXES } from '../../model';
import { fetchFromApi, select } from './controls';

/**
 * A resolver for getting the schema for a given model name.
 * @param {string} modelName
 * @return {Object} Retrieved schema.
 */
export function* getSchemaForModel( modelName ) {
	const path = getEndpoint( modelName );
	const schema = yield fetchFromApi( { path, method: 'OPTIONS' } );
	yield receiveSchemaForModel( modelName, schema );
	return schema;
}

/**
 * A resolver for getting the model entity factory for a given model name.
 * @param {string} modelName
 * @param {Object} schema
 * @return {Object} retrieved factory
 */
export function* getFactoryForModel( modelName, schema = {} ) {
	if ( ! isSchemaResponseOfModel( schema, modelName ) ) {
		schema = yield getSchemaByModel( modelName );
	}
	if ( ! isSchemaResponseOfModel( schema, modelName ) ) {
		return;
	}
	const factory = createEntityFactory(
		modelName,
		schema.schema,
		MODEL_PREFIXES( modelName )
	);
	yield receiveFactoryForModel( modelName, factory );
	return factory;
}

/**
 * A control for retrieving the schema for the given model
 * @param {string} modelName
 * @return {IterableIterator<*>|Object}  a generator or Object if schema is
 * retrieved.
 */
function* getSchemaByModel( modelName ) {
	let schema;
	const resolved = yield select( 'hasResolvedSchemaForModel', modelName );
	if ( resolved === true ) {
		schema = yield select( 'getSchemaForModel', modelName );
		return schema;
	}
	schema = yield getSchemaForModel( modelName );
	yield receiveSchemaForModel( modelName, schema );
	return schema;
}
