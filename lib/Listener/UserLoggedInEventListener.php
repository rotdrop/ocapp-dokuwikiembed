<?php
/**
 * DokuWikiEmbedded -- Embed DokuWiki into NextCloud with SSO.
 *
 * @author Claus-Justus Heine
 * @copyright 2020, 2021 Claus-Justus Heine <himself@claus-justus-heine.de>
 *
 * DokuWikiEmbedded is free software: you can redistribute it and/or
 * modify it under the terms of the GNU AFFERO GENERAL PUBLIC LICENSE
 * License as published by the Free Software Foundation; either
 * version 3 of the License, or (at your option) any later version.
 *
 * DokuWikiEmbedded is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU
 * AFFERO GENERAL PUBLIC LICENSE for more details.
 *
 * You should have received a copy of the GNU Affero General Public
 * License along with DokuWikiEmbedded. If not, see
 * <http://www.gnu.org/licenses/>.
 */

namespace OCA\DokuWikiEmbedded\Listener;

use OCP\User\Events\UserLoggedInEvent as Event1;
use OCP\User\Events\UserLoggedInWithCookieEvent as Event2;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;
use OCP\IRequest;
use OCP\ILogger;
use OCP\IL10N;

use OCA\DokuWikiEmbedded\Service\AuthDokuWiki;

class UserLoggedInEventListener implements IEventListener
{
  use \OCA\DokuWikiEmbedded\Traits\LoggerTrait;

  const EVENT = [ Event1::class, Event2::class ];

  /** @var string */
  private $appName;

  /** @var OCP\IRequest */
  private $request;

  /** @var OCA\DokuWikiEmbedded\Service\AuthDokuWiki */
  private $authenticator;

  public function __construct(
    AuthDokuWiki $authenticator
    , IRequest $request
    , ILogger $logger
    , IL10N $l10n
  ) {
    $this->authenticator = $authenticator;
    $this->appName = $this->authenticator->getAppName();
    $this->request = $request;
    $this->logger = $logger;
    $this->l = $l10n;
  }

  public function handle(Event $event): void {
    if (!($event instanceOf Event1 && !($event instanceOf Event2))) {
      return;
    }

    if ($this->ignoreRequest($this->request)) {
      return;
    }

    $userName = $event->getUser()->getUID();
    $password = $event->getPassword();
    if ($this->authenticator->login($userName, $password)) {
      // TODO: perhaps store in session and emit in middleware
      $this->authenticator->emitAuthHeaders();
      $this->logDebug("DokuWiki login of user $userName probably succeeded.");
    }
  }

  /**
   * In order to avoid request ping-pong the auto-login should only be
   * attempted for UI logins.
   */
  private function ignoreRequest(IRequest $request):bool
  {
    $method = $request->getMethod();
    if ($method != 'GET' && $method != 'POST') {
      $this->logDebug('Ignoring request with method '.$method);
      return true;
    }
    if ($request->getHeader('OCS-APIREQUEST') === 'true') {
      $this->logDebug('Ignoring API login');
      return true;
    }
    if (strpos($this->request->getHeader('Authorization'), 'Bearer ') === 0) {
      $this->logDebug('Ignoring API "bearer" auth');
      return true;
    }
    return false;
  }

}

// Local Variables: ***
// c-basic-offset: 2 ***
// indent-tabs-mode: nil ***
// End: ***
