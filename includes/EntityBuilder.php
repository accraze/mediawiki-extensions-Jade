<?php

/**
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

namespace Jade;

use Jade\Content\EntityContentHandler;
use WikiPage;
use Title;
use TitleValue;
use User;
use CentralIdLookup;

/**
 * Build and edit Jade Entity objects.
 * Also includes methods to perform each user action in the API.
 *
 * @license GPL-3.0-or-later
 * @author Andy Craze < acraze@wikimedia.org >
 */

class EntityBuilder {

	public function resolveTitle( $params, $create = null ) {
		if ( $params['title'] !== null ) {
			$title = '';
			if ( $create !== false ) {
				// clean title for entity lookup.
				$title = $this->cleanTitle( $params['title'] );
			}
			return $title;
		} else {
			// no title provided, so generate using entity data
			return $this->buildTitle( $params );
		}
	}

	public function cleanTitle( $title ) {
		// check if the Jade namespace is included.
		// $title = null;
		if ( strpos( $title, 'Jade' ) !== false ) {
			// remove unecessary 'Jade' substring
			$title = strstr( $title, ':' );
			$title = trim( substr( $title, 1 ) );
		}
		return $title;
	}

	public function buildTitle( $params ) {
		// create a title
		$data = json_decode( $params['entitydata'] );
		return ucwords( strtolower( $data->type ) ) . "/" . $data->id;
	}

	public function saveEntityPage( $titleStr, $text, $summary, $user = null ) {
		// Save an entity page to the DB.
		// first lookup the user.
		global $wgUser;
		if ( is_null( $user ) ) {
			$user = $wgUser;
		}
		// check if text should be encoded.
		if ( is_array( $text ) ) {
			$text = json_encode( $text );
		}
		// Build WikiPage object and save.
		$editTarget = new TitleValue( NS_JADE, $titleStr );
		$title = Title::newFromLinkTarget( $editTarget );
		$page = WikiPage::factory( $title );
		return $page->doEditContent(
			EntityContentHandler::makeContent( $text, $title ),
			$summary,
			0,
			false,
			$user
		);
	}

	public static function loadEntityPage( $titleStr ) {
		// Retrieve an Entity page from the DB
		$target = new TitleValue( NS_JADE, $titleStr );
		$title = Title::newFromLinkTarget( $target );
		$page = WikiPage::factory( $title );
		$content = $page->getContent();
		if ( $content === null ) {
			// no page found
			return null;
		}
		$entity = json_decode( $content->getNativeData(), true );
		return [ $page, $entity ];
	}

	public function hasPreferredLabels( $entity ) {
		// check if facets have a consensus proposal.
		$foundPreferred = false;
		foreach ( $entity['facets']as &$facet ) {
			foreach ( $facet['proposals'] as &$proposal ) {
				if ( $proposal['preferred'] ) {
					$foundPreferred = true;
				}
			}
		}
		return $foundPreferred;
	}

	public function setFirstPreferred( $entity ) {
		// Set the first proposal in a facet as preferred.
		foreach ( $entity['facets'] as $key => &$facet ) {
			$foundPreferred = false;
			foreach ( $facet['proposals'] as &$proposal ) {
				if ( $proposal['preferred'] ) {
					$foundPreferred = true;
				}
			}
			if ( !$foundPreferred && $facet['proposals'] && !empty( $facet['proposals'] ) ) {
				// set first proposal as 'preferred' or consensus.
				reset( $facet['proposals'] );
				$first_value = key( $facet['proposals'] );
				$entity['facets'][$key]['proposals'][$first_value]['preferred'] = true;
				$facet['proposals'] = $facet['proposals'];
			}
		}
		return $entity;
	}

	public function buildEntity( $params ) {
		// Build a new Jade Entity as an associative array.
		$facets = $this->buildFacets( $params );
		$data = [
			'facets' => $facets,
		];
		return $data;
	}

	public function buildFacets( $params ) {
		// Build Jade Entity facets object as assoc array.
		return [
			$params['facet'] => [
				'proposals' => $this->buildProposals( $params )
			]
		];
	}

	public function buildProposals( $params ) {
		// Build Jade Entity proposals as an assoc. array.
		return [
			$this->buildProposal( $params, true )
		];
	}

	public function buildProposal( $params, $preferred = false ) {
		// Build a single Jade Entity proposal as an assoc. array.
		$proposaldataname = $this->getProposalDataName( $params );
		return [
			$proposaldataname => json_decode( $params[$proposaldataname] ),
			'notes' => $params['notes'],
			'preferred' => $preferred,
			'author' => $this->buildAuthor( $params ),
			'endorsements' => $this->buildEndorsements( $params )
		];
	}

	public function buildEndorsements( $params ) {
		// Build Jade Entity endorsements as assoc. array.
		return [
			$this->buildEndorsement( $params )
		];
	}

	public function buildEndorsement( $params ) {
		// Build a single endorsement for Jade Entity proposal as an array.
		return [
				'author' => $this->buildAuthor( $params ),
				'comment' => $params['endorsementcomment'],
				'origin' => $params['endorsementorigin'],
				'created' => date( 'c' ),
				'touched' => date( 'c' )
			];
	}

	public function buildAuthor( $params ) {
		// Build a single Jade Entity author object as an array.
		$userdata = $this->getUserData( $params );
		if ( $userdata[0] === 0 || is_null( $userdata[0] ) ) {
			$id = $userdata[1];
		} else {
			$id = $userdata[0];
		}
		if ( is_string( $id ) ) {
			return [ 'ip' => $id ];
		}
		// Lookup CentralID
		global $wgUser;
		$cid = CentralIdLookup::factory()->centralIdFromLocalUser( $wgUser );
		return [ 'id' => $id, 'cid' => $cid ];
	}

	public function  getProposalDataName( $params ) {
		// Return the name of the proposal's 'data' field, depending on facet type.
		$proposaldataname = 'data';
		if ( $params['facet'] === 'editquality' ) {
			$proposaldataname = 'labeldata';
		}
		return $proposaldataname;
	}

	public function setPreferred( $params, $title, $contents ) {
		// Set the specified label as preferred.
		$warnings = [];
		$entity = $contents[1];
		$labelname = $this->getProposalDataName( $params );
		$label = json_decode( $params[$labelname], true );
		$facet = $params['facet'];
		$moved = false;

		foreach ( $entity['facets'][$facet]['proposals'] as &$proposal ) {
			if ( $proposal[$labelname] !== $label ) {
				// mark all other labels as not preferred.
				$proposal['preferred'] = false;
			} else {
				// mark proposal as preferred
				$proposal['preferred'] = true;
				$moved = true;
			}
		}
		if ( $moved === false ) {
			// no proposal found
			return [ 'jade-proposalnotfound', $entity, $warnings ];

		}
		// save updated entity
		$comment = '/* jade-setpreference */ ' . json_encode( $label );
		$status = $this->saveEntityPage( $title, $entity, $comment );
		return [ $status, $entity, $warnings ];
	}

	public function updateProposal( $params, $title, $contents ) {
		// Update a specific propopsal's notes fields.
		$warnings = [];
		$entity = $contents[1];
		$labelname = $this->getProposalDataName( $params );
		$label = json_decode( $params[$labelname], true );
		$facet = $params['facet'];
		$moved = false;

		foreach ( $entity['facets'][$facet]['proposals'] as &$proposal ) {
			// look for matching proposal
			if ( $proposal[$labelname] === $label ) {
				// update notes
				$proposal['notes'] = $params['notes'];
				$moved = true;
			}
		}
		if ( $moved === false ) {
			// no proposal found
			return [ 'jade-proposalnotfound', $entity, $warnings ];

		}
		// save updated entity
		$comment = '/* jade-updateproposal */ ' . json_encode( $label ) . ' "' .
			$params['notes'] . '" : ';
		$status = $this->saveEntityPage( $title, $entity, $comment );
		return [ $status, $entity, $warnings ];
	}

	public function deleteProposal( $params, $title, $contents ) {
		// Delete a specific proposal from within a given facet.
		$warnings = [];
		$entity = $contents[1];
		$labelname = $this->getProposalDataName( $params );
		$label = json_decode( $params[$labelname], true );
		$facet = $params['facet'];
		$num_endorsements = 0;
		$deleted = false;
		$proposals = $entity['facets'][$facet]['proposals'];
		$num_proposals = count( $proposals );
		foreach ( $proposals as $key => &$proposal ) {
			if ( $proposal[$labelname] === $label ) {
				if ( $num_proposals > 1 && $proposal['preferred'] === true ) {
					return [ 'jade-proposalispreferred', $entity, $warnings ];
				}
				array_splice( $entity['facets'][$facet]['proposals'], $key, 1 );
				$deleted = true;
				break;
			}
		}
		if ( $deleted === false ) {
			// no proposal found
			return [ 'jade-proposalnotfound', $entity, $warnings ];

		}
		// save updated entity
		$comment = '/* jade-deleteproposal|' . $num_endorsements . ' */ ' .
			json_encode( $label ) . ': ' . $params['comment'];
		$status = $this->saveEntityPage( $title, $entity, $comment );
		return [ $status, (array)$entity, $warnings ];
	}

	public function endorseProposal( $params, $title, $contents ) {
		$warnings = [];
		$entity = $contents[1];
		$labelname = $this->getProposalDataName( $params );
		$label = json_decode( $params[$labelname], true );
		$facet = $params['facet'];
		$endorsed = false;

		foreach ( $entity['facets'][$facet]['proposals'] as $key => &$proposal ) {
			if ( $proposal[$labelname] === $label ) {
				if ( !$this->isPreferredLabel( $proposal ) ) {
					$warnings[] = 'jade-endorsingnonpreferredproposal';
				}
				if ( !$this->userEndorsedProposal( $params, $contents ) ) {
					$endorsement = $this->buildEndorsement( $params );
					array_push( $proposal['endorsements'], $endorsement );
					$endorsed = true;
				}
			}
		}
		if ( $endorsed === false ) {
			// no proposal found
			return [ 'jade-proposalnotfound', $entity, $warnings ];

		}
		if ( $this->userAlreadyEndorsed( $params, [ null, $entity ] ) && $params['nomove'] ) {
			return [ 'jade-nochange', $entity, $warnings ];

		}
		// save updated entity
		$comment = '/* jade-endorseproposal */ ' . json_encode( $label ) . ' "' .
			$params['endorsementcomment'] . '": ' . $params['comment'];
		$status = $this->saveEntityPage( $title, $entity, $comment );
		return [ $status, $entity, $warnings ];
	}

	public function deleteEndorsement( $params, $title, $contents ) {
		// Delete a specific Endorsement for a Proposal.
		$warnings = [];
		$entity = $contents[1];
		$labelname = $this->getProposalDataName( $params );
		$label = json_decode( $params[$labelname], true );
		$facet = $params['facet'];
		$userdata = $this->getUserData( $params );
		$deleted = false;
		$proposalFound = false;

		foreach ( $entity['facets'][$facet]['proposals'] as $pkey => &$proposal ) {
			// find target proposal
			if ( $proposal[$labelname] === $label ) {
				$proposalFound = true;
				foreach ( $proposal['endorsements'] as $key => &$endorsement ) {
					// match endorsement by user id.
					if ( $userdata[0] === 0 ) {
						// anonymous user so look at IP
						if ( $userdata[1] === $endorsement['author']['ip'] ) {
							unset( $entity['facets'][$facet]['proposals'][$pkey]['endorsements'][$key] );
							$deleted = true;
							break;
						}
					}
					if ( $userdata[0] === null ) {
						// check global id case
						if ( $userdata[1] === $endorsement['author']['cid'] ) {
							unset( $entity['facets'][$facet]['proposals'][$pkey]['endorsements'][$key] );
							$deleted = true;
							break;
						}
					}
					// otherwise just target id
					if ( $userdata[0] === $endorsement['author']['id'] ) {
						unset( $entity['facets'][$facet]['proposals'][$pkey]['endorsements'][$key] );
						$deleted = true;
						break;
					}
				}
			}
		}
		if ( $proposalFound === false ) {
			// no proposal found
			return [ 'jade-proposalnotfound', $entity, $warnings ];

		}
		if ( $deleted === false ) {
			// no endorsement found
	  return [ 'jade-endorsementnotfound', $entity, $warnings ];

		}
		// save updated entity
		$comment = '/* jade-deleteendorsement */ ' . json_encode( $label ) .
			' by id ' . $userdata[1] . ': ' . $params['comment'];
		$status = $this->saveEntityPage( $title, $entity, $comment );
		return [ $status, $entity, $warnings ];
	}

	public function createAndEndorse( $params, $title, $user = null ) {
		// Create a new Jade Entity with a single proposal and one endorsement.
		$warnings = [];
		$entity = $this->buildEntity( $params );
		if ( $this->userAlreadyEndorsed( $params, [ null, $entity ] ) && $params['nomove'] ) {
			return [ 'jade-nochange', $entity, $warnings ];

		}
		$labelname = $this->getProposalDataName( $params );
		$label = json_decode( $params[$labelname], true );
		$comments = '/* jade-createandendorseproposal */ ' . json_encode( $label ) .
			' "' . $params['notes'] . '" : ' . $params['comment'];
		$status = $this->saveEntityPage( $title, $entity, $comments, $user );
		return [ $status, $entity, $warnings ];
	}

	public function updateEndorsement( $params, $title, $contents ) {
		// Update a user's endorsement on a specific proposal.
		$warnings  = [];
		$entity = $contents[1];
		$labelname = $this->getProposalDataName( $params );
		$label = json_decode( $params[$labelname], true );
		$facet = $params['facet'];
		$userdata = $this->getUserData( $params );
		$updated = false;
		$proposalFound = false;

		foreach ( $entity['facets'][$facet]['proposals'] as $pkey => &$proposal ) {
			// lookup target proposal
			if ( $proposal[$labelname] === $label ) {
				$proposalFound = true;
				foreach ( $proposal['endorsements'] as $key => &$endorsement ) {
					if ( $userdata[0] === 0 ) {
						// anonymous user so look at IP
						if ( $userdata[1] === $endorsement['author']['ip'] ) {
							$endorsement['comment'] = $params['endorsementcomment'];
							$endorsement['touched'] = date( 'c' );
							$updated = true;
						}
					}
					if ( $userdata[0] === null ) {
						// check global id case
						if ( $userdata[1] === $endorsement['author']['cid'] ) {
							$endorsement['comment'] = $params['endorsementcomment'];
							$endorsement['touched'] = date( 'c' );
							$updated = true;
						}
					}
					// otherwise just target id
					$id = $this->arrayValue( $endorsement['author'], 'id' );
					if ( $userdata[0] === $id ) {
						$endorsement['comment'] = $params['endorsementcomment'];
						$endorsement['touched'] = date( 'c' );
						$updated = true;
					}
				}
			}
		}
		if ( $proposalFound === false ) {
			// no proposal found
			return [ 'jade-proposalnotfound', $entity, $warnings ];
		}
		if ( $updated === false ) {
			// no endorsement found
			return [ 'jade-endorsementnotfound', $entity, $warnings ];
		}

		// save updated entity
		$comment = '/* jade-updateendorsement */ ' . json_encode( $label ) .
			' "' . $params['endorsementcomment'] . '" : ' . $params['comment'];
		$status = $this->saveEntityPage( $title, $entity, $comment );
		return [ $status, $entity, $warnings ];
	}

  public function isPreferredLabel( $proposal ) {
		// Checks if proposal has preferred bit. Returns bool.
		$preferred = false;
		if ( $proposal['preferred'] ) {
			$preferred = true;
		}
		return $preferred;
	 }

	public function moveEndorsement( $params, $title, $contents ) {
		// Moves an endorsements from one proposal to another.
		$warnings = [];
		$entity = $contents[1];
		$labelname = $this->getProposalDataName( $params );
		$label = json_decode( $params[$labelname], true );
		$facet = $params['facet'];
		$userdata = $this->getUserData( $params );
		$proposalFound = false;
		$moved = false;
		$origin = null;
		$targetIdx = null;
		$oldLabelData = null;

		foreach ( $entity['facets'][$facet]['proposals'] as $pkey => &$proposal ) {
			if ( $proposal[$labelname] === $label ) {
				$proposalFound = true;
				$targetIdx = $pkey;
			}
			foreach ( $proposal['endorsements'] as $key => &$endorsement ) {
				// target endorsement by user
				if ( $userdata[0] === 0 ) {
					// anonymous user so look at IP
					if ( $userdata[1] === $endorsement['author']['ip'] ) {
						$origin = clone $endorsement;
						unset( $entity['facets'][$facet]['proposals'][$pkey]['endorsements'][$key] );
						$moved = true;
						$oldLabelData = $proposal[$labelname];
					}
				}
				if ( $userdata[0] === null ) {
					// check global id case
					if ( $userdata[1] === $endorsement['author']['cid'] ) {
						$origin = clone $endorsement;
						unset( $entity['facets'][$facet]['proposals'][$pkey]['endorsements'][$key] );
						$moved = true;
						$oldLabelData = $proposal[$labelname];
					}
				}
				// otherwise just target id
				if ( $userdata[0] === $endorsement['author']['id'] ) {
					$origin = $endorsement;
					unset( $entity['facets'][$facet]['proposals'][$pkey]['endorsements'][$key] );
					$moved = true;
					$oldLabelData = $proposal[$labelname];
				}
			}
		}
		if ( $proposalFound === false || $targetIdx === null ) {
			// no proposal found
			return [ 'jade-proposalnotfound', $entity, $warnings ];

		}

		if ( $moved === false ) {
			// no endorsement found
			return [ 'jade-endorsementnotfound', $entity, $warnings ];

		}

		$proposal = $endorsements = &$entity['facets'][$facet]['proposals'][$targetIdx];
		if ( !$this->isPreferredLabel( $proposal ) ) {
			// warn that you are endorsing a non-preferred proposal
			$warnings[] = 'jade-endorsingnonpreferredproposal';
		}
		if ( is_null( $params['endorsementcomment'] ) ) {
			// copy over previous endorsement comment.
			$params['endorsementcomment'] = $origin['comment'];
		}
		// copy endorsement origin
		$params['endorsementorigin'] = $origin['origin'];

		$endorsements = &$entity['facets'][$facet]['proposals'][$targetIdx]['endorsements'];
		if ( is_array( $endorsements ) === false ) {
			$endorsements = [];
		}
		array_push( $endorsements,  $this->buildEndorsement( $params ) );
		// save updated entity
		$comment = '/* jade-moveendorsement */ ' . 'from ' . json_encode( $oldLabelData ) .
			' to ' . json_encode( $label ) . ' "' . $params['endorsementcomment'] .
			'": ' . $params['comment'];
		$status = $this->saveEntityPage( $title, $entity, $comment );
		return [ $status, $entity, $warnings ];
	}

	public function doesFacetContainProposal( $params, $contents ) {
		// checks if target proposal is contained within target facet.
		$entity = $contents[1];
		$labelname = $this->getProposalDataName( $params );
		$label = json_decode( $params[$labelname], true );
		$facet = $params['facet'];
		$found = false;
		foreach ( $entity['facets'][$facet]['proposals'] as $pkey => &$proposal ) {
			// look for proposal in facet
			if ( $proposal[$labelname] === $label ) {
				$found = true;
				break;
			}
		}
		return $found;
	}

	public function userEndorsedProposal( $params, $contents ) {
		// Checks if user has endorsed the target proposal. Returns bool.
		$entity = $contents[1];
		$labelname = $this->getProposalDataName( $params );
		$label = json_decode( $params[$labelname], true );
		$facet = $params['facet'];
		$userdata = $this->getUserData( $params );
		$userEndorsed = false;
		foreach ( $entity['facets'][$facet]['proposals'] as $pkey => &$proposal ) {
			// lookup target proposal
			if ( $proposal[$labelname] === $label ) {
				foreach ( $proposal['endorsements'] as $key => &$endorsement ) {
					// look for endorsement from user
					if ( $this->userMatch( $userdata, $endorsement['author'] ) === true ) {
						$userEndorsed = true;
						break;
					}
				}
			}
		}
		return $userEndorsed;
	}

	public function userEndorsedAnyProposal( $params, $contents ) {
		// Checks to see if any proposals have been endorsed by current user.
		// Returns bool.
		$entity = $contents[1];
		$labelname = $this->getProposalDataName( $params );
		$label = json_decode( $params[$labelname], true );
		$facet = $params['facet'];
		$userdata = $this->getUserData( $params );
		$userEndorsed = false;
		foreach ( $entity['facets'][$facet]['proposals'] as $pkey => &$proposal ) {
			if ( $proposal[$labelname] !== $label ) {
				foreach ( $proposal['endorsements'] as $key => &$endorsement ) {
					if ( $this->userMatch( $userdata, $endorsement['author'] ) === true ) {
						$userEndorsed = true;
						break;
					}
				}
			}
		}
		return $userEndorsed;
	}

	public function userMatch( $userdata, $authordata ) {
		// Checks if userdata matches entity author data.
		// Returns bool.
		$match = false;
		if ( $userdata[0] === 0 ) {
			// anonymous user so look at IP
			if ( $userdata[1] === $authordata['ip'] ) {
					$match = true;
			}
		}
		if ( $userdata[0] === null ) {
			// check global id case
			if ( $userdata[1] === $authordata['cid'] ) {
				$match = true;
			}
		}
		// otherwise just target id
		$id = $this->arrayValue( $authordata, 'id' );
		if ( $userdata[0] === $id ) {
			$match = true;
		}
		return $match;
	}

	public function proposeOrEndorse( $params, $title, $contents ) {
		// A catch-all routing method that tries to *do the right thing*.
		// It resolves the context based on the parameters passed in and
		// the state of the DB.
		//
		// does entity page exist?
		if ( is_null( $contents ) ) {
			// page does not exist
			// so create page with a proposal and endorse it.
			if ( is_null( $params['endorsementcomment'] ) ) {
				$params['endorsementcomment'] = 'As proposer';
			}
			$params['nomove'] = false;
			return $this->createAndEndorse( $params, $title );
		} else {
			// entity page exists
			// Does the target facet already have a proposal with the target
			// labeldata?
			$facetInfo = $this->doesFacetContainProposal( $params, $contents );
			if ( $this->doesFacetContainProposal( $params, $contents ) ) {
				// Has the user already endorsed the target proposal?
				if ( $this->userEndorsedProposal( $params, $contents ) ) {
					// update endorsementcomment if set & warn
					// existingproposalnotesnotoverwritten
					return $this->updateEndorsement( $params,  $title, $contents );
				} else {
					// Has the user endorsed any proposals already?
					if ( $this->userEndorsedAnyProposal( $params, $contents ) ) {
						// move endorsement to target proposal & warn
						// existingproposalnotesoverwritten
						return $this->moveEndorsement( $params, $title, $contents );
					}
					// endorse the proposal
					$params['nomove'] = false;
					return $this->endorseProposal( $params, $title, $contents );
				}
			} else {
				// proposal does not exist
				// lets make one
				// Has the user already endorsed a proposal within the target facet?
				if ( $this->userAlreadyEndorsed( $params, $contents ) ) {
					// create proposal and move endorsement with updated
					// endorsementcomment (comment: /*
					// jade-createproposalandmoveendorsement */
					return $this->createProposalAndMoveEndorsement( $params, $title, $contents );
				} else {
					// create proposal and endorsement
					return $this->createProposalAndEndorsement( $params, $title, $contents );
				}
			}
		}
	}

	public function userAlreadyEndorsed( $params, $contents ) {
		// Has the user already endorsed any proposal within the target facet?
		$entity = $contents[1];
		$labelname = $this->getProposalDataName( $params );
		$label = json_decode( $params[$labelname], true );
		$facet = $params['facet'];
		$userdata = $this->getUserData( $params );
		$userEndorsed = false;
		foreach ( $entity['facets'][$facet]['proposals'] as $pkey => &$proposal ) {
			foreach ( $proposal['endorsements'] as $key => &$endorsement ) {
					if ( $this->userMatch( $userdata, $endorsement['author'] ) === true ) {
						$userEndorsed = true;
						break;
					}
			}
		}
		return $userEndorsed;
	}

	public function createProposalAndMoveEndorsement( $params, $title, $contents ) {
		// Create a new proposal and move a previous endorsement to it.
		$warnings  = [];
		$entity = $contents[1];
		$labelname = $this->getProposalDataName( $params );
		$label = json_decode( $params[$labelname] );
		$facet = $params['facet'];
		$userdata = $this->getUserData( $params );
		$moved = false;
		array_push( $entity['facets'][$facet]['proposals'], $this->buildProposal( $params ) );
		foreach ( $entity['facets'][$facet]['proposals'] as $pkey => &$proposal ) {
			if ( $proposal[$labelname] != $label ) {
				foreach ( $proposal['endorsements'] as $key => &$endorsement ) {
					if ( $userdata[0] === 0 ) {
						// anonymous user so look at IP
						if ( $userdata[1] === $endorsement['author']['ip'] ) {
							unset( $entity['facets'][$facet]['proposals'][$pkey]['endorsements'][$key] );
							$moved = true;
							break 2;
						}
					}
					if ( $userdata[0] === null ) {
						// check global id case
						if ( $userdata[1] === $endorsement['author']['cid'] ) {
							unset( $entity['facets'][$facet]['proposals'][$pkey]['endorsements'][$key] );
							$moved = true;
							break 2;
						}
					}
					// otherwise just target id
					if ( $userdata[0] === $endorsement['author']['id'] ) {
						unset( $entity['facets'][$facet]['proposals'][$pkey]['endorsements'][$key] );
						$moved = true;
						break 2;
					}
				}
			}
		}
		if ( $moved === false ) {
			// no endorsement found
			return [ 'jade-endorsementnotfound', $entity, $warnings ];

		}
		$comments = '/* jade-createproposalandmoveendorsement */ ' . json_encode( $label ) .
			' "' . $params['notes'] . '" : ' . $params['comment'];
		$status = $this->saveEntityPage( $title, $entity, $comments );
		return [ $status, $entity, $warnings ];
	}

	public function createProposalAndEndorsement( $params, $title, $contents ) {
		// Create a new proposal with a single endorsement as assoc array.
		$warnings = [];
		$entity = $contents[1];
		$labelname = $this->getProposalDataName( $params );
		$label = json_decode( $params[$labelname], true );
		$facet = $params['facet'];
		$userdata = $this->getUserData( $params );
		if ( is_null( $params['endorsementcomment'] ) ) {
			$params['endorsementcomment'] = 'As proposer.';
		}
		$entity['facets'][$facet]['proposals'][] = $this->buildProposal( $params );
		$comments = '/* jade-createandendorseproposal */ ' . json_encode( $label ) .
			' "' . $params['notes'] . '" : ' . $params['comment'];
		$status = $this->saveEntityPage( $title, $entity, $comments );
		return [ $status, $entity, $warnings ];
	}

	public function getUserData( $params ) {
		// Retrive user data for Entity author objects.
		// Returns array
		global $wgUser;
		$ip = $this->arrayValue( $params, 'ip' );
		$globalId = $this->arrayValue( $params, 'global_id' );
		$userId = $this->arrayValue( $params, 'user_id' );
		if ( $ip === null && $globalId === null && $userId === null ) {
			$user = $wgUser;
			return [ $user->getId(), $user->getName() ];
		}

		if ( $globalId !== null ) {
			// Perform CentralIdLookup
			$cid = CentralIdLookup::factory()->centralIdFromLocalUser( $wgUser );
			return [ null, $cid ];
		}

		if ( $userId !== null ) {
			$user = User::newFromId( $params['user_id'] );
			return [ $params['user_id'], $params['user_id'] ];
		} elseif ( $ip !== null ) {
			return [ 0, $params['ip'] ];
		}
 }

	public function arrayValue( $array, $key, $default_value = null ) {
		// Check if array contains a specific key. Returns value or null.
		return is_array( $array ) && array_key_exists( $key, $array ) ? $array[$key] : $default_value;
	}
}
