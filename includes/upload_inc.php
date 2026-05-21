<?php
/**
 * @package stock
 * @subpackage functions
 */

use Bitweaver\Stock\StockComponent;
use Bitweaver\Stock\StockAssembly;
use Bitweaver\BitBase;
use Bitweaver\KernelTools;

/**
 * stock_handle_upload
 */
function stock_handle_upload( &$pFiles ) {
	global $gBitUser, $gContent, $gBitSystem, $stockErrors, $stockWarnings, $stockSuccess, $gStockUploads;

	// first of all set the execution time for this process to unlimited
	set_time_limit(0);

	$upImages = [];
	$upArchives = [];
	$upErrors = [];
	$upData = [];

	$i = 0;
	usort( $pFiles, 'stock_sort_uploads' );

	// No gallery was specified, let's try to find one or create one.
	if( empty( $_REQUEST['gallery_additions'] ) ) {
		if( $gBitUser->hasPermission( 'p_stock_create' )) {
			$_REQUEST['gallery_additions'] = [ stock_get_default_gallery_id( $gBitUser->mUserId, $gBitUser->getDisplayName()."'s Gallery" ) ];
		} else {
			$gBitSystem->fatalError( KernelTools::tra( "You don't have permissions to create a new gallery. Please select an existing one to insert your images to." ));
		}
	}

	foreach( array_keys( $pFiles ) as $key ) {
		if ( !empty($pFiles[$key]['name']) ) {
			$pFiles[$key]['type'] = $gBitSystem->verifyMimeType( $pFiles[$key]['tmp_name'] );
			if( preg_match( '/(^image|pdf|vnd)/i', $pFiles[$key]['type'] ?? '' ) ) {
				$upImages[$key] = $pFiles[$key];
				// clone the request data so edit service values are passed into store process
				$upData[$key] = $_REQUEST;
				// add the form data for each upload
				if( !empty( $_REQUEST['imagedata'][$i] ) ) {
					array_merge( $upData[$key], $_REQUEST['imagedata'][$i] );
				}
			} elseif( !empty( $pFiles[$key]['tmp_name'] ) && !empty( $pFiles[$key]['name'] ) ) {
				$upArchives[$key] = $pFiles[$key];
			}
			$i++;
		}
	}

	foreach( array_keys( $upArchives ) as $key ) {
		$upErrors = stock_process_archive( $upArchives[$key], $gContent, true );
	}

	foreach( array_keys( $upImages ) as $key ) {
		// resize original if we the user requests it
		if( !empty( $_REQUEST['resize'] ) ) {
			$upImages[$key]['resize'] = $_REQUEST['resize'];
		}
		$upErrors = array_merge( $upErrors, stock_store_upload( $upImages[$key], $upData[$key], !empty( $_REQUEST['rotate_image'] )));
	}

	if( !is_object( $gContent ) || !$gContent->isValid() ) {
		$gContent = new StockAssembly( $_REQUEST['gallery_additions'][0] );
		$gContent->load();
	}

	if( !empty( $gStockUploads ) ){
		$_REQUEST['uploaded_objects'] = &$gStockUploads;
		$gContent->invokeServices( "content_post_upload_function", $_REQUEST );
	}

	return $upErrors;
}

/**
 * stock_sort_upload
 */
function stock_sort_uploads( $a, $b ) {
	return strnatcmp( $a['name'], $b['name'] );
}

/**
 * stock_get_default_gallery_id
 */
function stock_get_default_gallery_id( $pUserId, $pNewName ) {
	global $gBitUser;
	$gal = new StockAssembly();
	$getHash = [ 'user_id' => $pUserId, 'max_records' => 1, 'sort_mode' => 'created_desc', 'show_empty' => true ];
	$upList = $gal->getList( $getHash );
	if( !empty( $upList ) ) {
		$ret = key( $upList );
	} else {
		$galleryHash = [ 'title' => $pNewName ];
		if( $gal->store( $galleryHash ) ) {
			$ret = $gal->mGalleryId;
		}
	}

	global $gContent;
	if( $ret && (!is_object( $gContent ) || !$gContent->isValid()) ) {
		$gContent = new StockAssembly( $ret );
		$gContent->load();
	}
	return $ret;
}

/**
 * stock_store_upload
 */
function stock_store_upload( &$pFileHash, $pImageData = [], $pAutoRotate=true ) {
	global $gBitSystem, $gStockUploads;
	$ret = [];

	// verifyMimeType to make sure we are working with the proper file type assumptions
	$pFileHash['type'] = $gBitSystem->verifyMimeType($pFileHash['tmp_name']);
	if( !empty( $pFileHash ) && ( $pFileHash['size'] > 0 ) && is_file( $pFileHash['tmp_name'] ) && stock_verify_upload_item(  $pFileHash ) ) {
		// make a copy for each image we need to store
		$image = new StockComponent();
		// Store/Update the image
		$pImageData['_files_override'] = [ $pFileHash ];
		$pImageData['process_storage'] = STORAGE_IMAGE;
		$pImageData['purge_from_galleries'] = true;
		// store the image
		if( !$image->store( $pImageData ) ) {
			$ret = $image->mErrors;
		} else {
			$pFileHash['content_id'] = $image->getField( 'content_id' );
			$pFileHash['image_id'] = $image->getField( 'image_id' );
			$image->load();
			// play with image some more if user has requested it
			if( $pAutoRotate ) {
				$image->rotateImage( 'auto' );
			}
			if( !($galleryAdditions = BitBase::getParameter( $pImageData, 'gallery_additions' )) ) {
				global $gBitUser;
				$galleryAdditions = [ stock_get_default_gallery_id( $gBitUser->mUserId, $gBitUser->getDisplayName()."'s Gallery" ) ];
			}
			$image->addToGalleries( $galleryAdditions );
			$gStockUploads[] = $image;
		}

		// when we're using xupload, we need to remove temp files manually
		if ( is_readable( $pFileHash['tmp_name'] ) ) @unlink( $pFileHash['tmp_name'] );
	}
	return $ret;
}

/**
 * Recursively builds a tree where each directory represents a gallery, and files are assumed to be images.
 */
function stock_process_archive( &$pFileHash, &$pParentGallery, $pRoot=false ) {
	global $gBitSystem, $gBitUser;
	$errors = [];

	if( ( $destDir = \Bitweaver\Liberty\liberty_process_archive( $pFileHash ) ) && ( !empty( $_REQUEST['process_archive'] ) || !$gBitUser->hasPermission( 'p_stock_upload_nonimages' ) ) ) {
		if( empty( $pParentGallery ) && !is_file( $pFileHash['tmp_name'] ) ) {
			$pParentGallery = new StockAssembly();
			$galleryHash = [ 'title' => basename( $destDir ) ];
			if( !$pParentGallery->store( $galleryHash ) ) {
				$errors = array_merge( $errors, array_values( $pParentGallery->mErrors ) );
			}
			global $gContent;
			$gContent = &$pParentGallery;
		}

		if( !empty( $pParentGallery ) ) {
			$pFileHash['gallery_id'] = $pParentGallery->getField( 'gallery_id' );
		}
		stock_process_directory( $destDir, $pParentGallery, $pRoot );
	} else {
		global $gBitUser;
		if( $gBitUser->hasPermission( 'p_stock_upload_nonimages' ) ) {
			$errors = array_merge( $errors, stock_store_upload( $pFileHash ));
		} else {
			$errors['upload'] = KernelTools::tra( 'Your upload could not be processed because it was determined to be a non-image and you only have permission to upload images.' );
		}
	}
	return $errors;
}

if( !function_exists( 'stock_verify_upload_item' ) ) {
// Possible override
function stock_verify_upload_item( $pScanFile ) {
	global $gBitUser;
	return $gBitUser->hasPermission( 'p_stock_upload_nonimages' ) || preg_match( '/^video\/*/', $pScanFile['type'] ) || preg_match( '/^image\/*/', $pScanFile['type'] ) || preg_match( '/pdf/i', $pScanFile['type'] );
}
}

/**
 * Recursively builds a tree where each directory represents a gallery, and files are assumed to be images.
 */
function stock_process_directory( $pDestinationDir, &$pParentGallery, $pRoot=false ) {
	global $gBitSystem, $gBitUser;
	$errors = [];
	if( $archiveDir = opendir( $pDestinationDir ) ) {
		$order = 100;
		while( $fileName = readdir($archiveDir) ) {
			$sortedNames[] = $fileName;
		}
		sort( $sortedNames );
		foreach( $sortedNames as $fileName ) {
			if( $fileName == 'Thumbs.db' ) {
				unlink( "$pDestinationDir/$fileName" );
			}
			if( !preg_match( '/^\./', $fileName ) && ( $fileName != 'Thumbs.db' ) ) {
				$mimeResults = $gBitSystem->verifyFileExtension( $pDestinationDir.'/'.$fileName );
				$scanFile = [
					'type' => $mimeResults[1],
					'name' => $fileName,
					'size' => filesize( "$pDestinationDir/$fileName" ),
					'tmp_name' => "$pDestinationDir/$fileName",
				];

				if( !empty( $_REQUEST['resize'] ) && is_numeric( $_REQUEST['resize'] ) ) {
					$scanFile['max_height'] = $scanFile['max_width'] = $_REQUEST['resize'];
				}

				if( is_dir( $pDestinationDir.'/'.$fileName ) ) {
					if( $fileName == '__MACOSX' ) {
						// Mac OS resources file
						KernelTools::unlink_r( $pDestinationDir.'/'.$fileName );
					} else {
						// We found a new Gallery!
						$newGallery = new StockAssembly();
						$galleryHash = [ 'title' => str_replace( '_', ' ', $fileName ) ];
						if( $newGallery->store( $galleryHash ) ) {
							if( $pRoot ) {
								$newGallery->addToGalleries( $_REQUEST['gallery_additions'] );
							}
							if( is_object( $pParentGallery ) ) {
								$pParentGallery->addItem( $newGallery->mContentId, $order );
							}
							//recurse down in!
							$errors = array_merge( $errors, stock_process_archive( $scanFile, $newGallery ) );
						} else {
							$errors = array_merge( $errors, array_values( $newGallery->mErrors ) );
						}
					}
				} elseif( preg_match( '/.+\/*zip*/', $scanFile['type'] ) ) {
					//recurse down in!
					$errors = array_merge( $errors, stock_process_archive( $scanFile, $pParentGallery ) );
				} elseif( stock_verify_upload_item( $scanFile ) ) {
					$newImage = new StockComponent();
					$imageHash = [ '_files_override' => [ $scanFile ] ];
					if( $newImage->store( $imageHash ) ) {
						if( $pRoot ) {
							$newImage->addToGalleries( $_REQUEST['gallery_additions'] );
						}
						if( !is_object( $pParentGallery ) ) {
							global $gBitUser;
							$pParentGallery = new StockAssembly( stock_get_default_gallery_id( $gBitUser->mUserId, $gBitUser->getDisplayName()."'s Gallery" ) );
							$pParentGallery->load();
						}
						$pParentGallery->addItem( $newImage->mContentId );
					} else {
						$errors = array_merge( $errors, array_values( $newImage->mErrors ) );
					}
				} elseif( is_file( $scanFile['tmp_name'] ) ) {
					// unknown file type, let's be tidy and clean it up
					unlink( $scanFile['tmp_name'] );
				}
				$order += 10;
			}
		}
		if ( !KernelTools::is_windows() ) {
			KernelTools::unlink_r( $pDestinationDir );
		}
	}
	return $errors;
}

// this function will process a directory and all it's sub directories without
// making any assumptions. hierarchy of sub directories is maintained and
// archives can be processed or simply added to the galleries.
function stock_process_ftp_directory( $pProcessDir ) {
	global $gBitSystem, $gBitUser;
	if( empty( $_REQUEST['gallery_additions'] ) ) {
		$_REQUEST['gallery_additions'] = [];
	}

	$errors = [];
	if( $archiveDir = opendir( $pProcessDir ) ) {
		$order = 100;
		while( $fileName = readdir($archiveDir) ) {
			$sortedNames[] = $fileName;
		}

		sort( $sortedNames );
		$order = 100;

		foreach( $sortedNames as $fileName ) {
			if( !preg_match( '/^\./', $fileName ) && ( $fileName != 'Thumbs.db' ) ) {
				$scanFile = [
					'type'     => $gBitSystem->lookupMimeType( substr( $fileName, strrpos( $fileName, '.' ) + 1 )  ),
					'name'     => $fileName,
					'size'     => filesize( "$pProcessDir/$fileName" ),
					'tmp_name' => "$pProcessDir/$fileName",
				];

				if( is_dir( $pProcessDir.'/'.$fileName ) ) {
					// create a new gallery from directory
					$dirGallery = new StockAssembly();
					$galleryHash = [ 'title' => str_replace( '_', ' ', $fileName ) ];
					if( $dirGallery->store( $galleryHash ) ) {
						$dirGallery->addToGalleries( $_REQUEST['gallery_additions'] );
						$errors = array_merge( $errors, stock_process_directory( $pProcessDir.'/'.$fileName, $dirGallery ) );
					} else {
						$errors = array_merge( $errors, array_values( $dirGallery->mErrors ) );
					}
					unset( $dirGallery );
				} else {
					if( preg_match( '/(^image|pdf)/i', $scanFile['type'] ) ) {
						// process image
						$newImage = new StockComponent();
						$imageHash = [ 'upload' => $scanFile ];
						if( $newImage->store( $imageHash ) ) {
							$newImage->addToGalleries( $_REQUEST['gallery_additions'] );

							// if we have a gallery to add these images to, load one of them
							if( !empty( $_REQUEST['gallery_additions'][0] ) && @!is_object( $imageGallery ) ) {
								$imageGallery = new StockAssembly();
								$imageGallery->mGalleryId = $_REQUEST['gallery_additions'][0];
								$imageGallery->load();
							}

							if( @!is_object( $imageGallery ) ) {
								global $gBitUser;
								$galleryHash = [ 'title' => $gBitUser->getDisplayName()."'s Gallery" ];
								$imageGallery = new StockAssembly();
								if( $imageGallery->store( $galleryHash ) ) {
									$imageGallery->load();
								} else {
									$errors = array_merge( $errors, array_values( $imageGallery->mErrors ) );
								}
							}

							$imageGallery->addItem( $newImage->mContentId );
						} else {
							$errors = array_merge( $errors, array_values( $newImage->mErrors ) );
						}
					} else {
						// create a new gallery from archive
						$archiveGallery = new StockAssembly();
						$galleryHash = [ 'title' => substr( $fileName, 0, str_replace( '_', ' ', strrpos( $fileName, '.' ) ) ) ];
						if( !$archiveGallery->store( $galleryHash ) ) {
							$errors = array_merge( $errors, array_values( $archiveGallery->mErrors ) );
						}

						$errors = stock_process_archive( $scanFile, $archiveGallery, true );
						unset( $archiveGallery );
					}
				}
				$order += 10;
			}
		}
	}
	return $errors;
}
