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

import { webPrefix } from './config.js';
import ajaxFailData from './ajax.js';
import generateUrl from './generate-url.js';

const jQuery = require('jquery');
const $ = jQuery;
require('./nextcloud/jquery/requesttoken.js');

const storeSettings = function(event, $id) {
  const msg = $('#' + webPrefix + 'settings .msg');
  if ($.trim(msg.html()) === '') {
    msg.hide();
  }
  let post = $id.serialize();
  const cbSelector = 'input:checkbox:not(:checked)';
  $id.find(cbSelector).addBack(cbSelector).each(function(index) {
    console.info('unchecked?', index, $(this));
    if (post !== '') {
      post += '&';
    }
    post += $(this).attr('name') + '=' + 'off';
  });
  $.post(generateUrl('settings/admin/set'), post)
    .done(function(data) {
      console.info('Got response data', data);
      if (data.value) {
        $id.val(data.value);
      }
      if (data.message) {
        msg.html(data.message);
        msg.show();
      }
    })
    .fail(function(xhr, status, errorThrown) {
      const response = ajaxFailData(xhr, status, errorThrown);
      console.error(response);
      if (response.message) {
        msg.html(response.message);
        msg.show();
      }
    });
  return false;
};

$(function() {

  const inputs = {
    externalLocation: 'blur',
    authenticationRefreshInterval: 'blur',
    enableSSLVerify: 'change',
  };

  for (const input in inputs) {
    const $id = $('#' + input);
    const event = inputs[input];

    console.info(input, event);

    $id.on(event, function(event) {
      event.preventDefault();
      storeSettings(event, $id);
      return false;
    });
  }
});

// Local Variables: ***
// js-indent-level: 2 ***
// indent-tabs-mode: nil ***
// End: ***
