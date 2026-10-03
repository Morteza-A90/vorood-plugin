document.addEventListener('DOMContentLoaded', function () {
    const voorodakAdminAjax = window.voorodak_admin_ajax || {};

    function voorodakAdminQuery(selector, context) {
        return (context || document).querySelector(selector);
    }

    function voorodakAdminQueryAll(selector, context) {
        return Array.prototype.slice.call((context || document).querySelectorAll(selector));
    }

    function voorodakAdminShow(selector, display, context) {
        voorodakAdminQueryAll(selector, context).forEach(function (element) {
            if (display) {
                element.style.display = display;
            } else if (element.tagName && element.tagName.toLowerCase() === 'tr') {
                element.style.display = '';
            } else {
                element.style.display = 'block';
            }
        });
    }

    function voorodakAdminHide(selector, context) {
        voorodakAdminQueryAll(selector, context).forEach(function (element) {
            element.style.display = 'none';
        });
    }

    function voorodakAdminSetHtml(selector, html, context) {
        const element = typeof selector === 'string' ? voorodakAdminQuery(selector, context) : selector;
        if (element) {
            element.innerHTML = html || '';
        }
    }

    function voorodakAdminSetText(selector, text, context) {
        const element = typeof selector === 'string' ? voorodakAdminQuery(selector, context) : selector;
        if (element) {
            element.textContent = text == null ? '' : String(text);
        }
    }

    function voorodakAdminSetValue(selector, value, context) {
        const element = typeof selector === 'string' ? voorodakAdminQuery(selector, context) : selector;
        if (element) {
            element.value = value || '';
        }
    }

    function voorodakAdminPost(data, successCallback, errorCallback) {
        fetch(voorodakAdminAjax.ajax_url, {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8'
            },
            body: new URLSearchParams(data).toString()
        }).then(function (response) {
            return response.json();
        }).then(successCallback).catch(function () {
            if (typeof errorCallback === 'function') {
                errorCallback();
            }
        });
    }

    const downloadUsersButton = voorodakAdminQuery('#download_list_users');
    if (downloadUsersButton) {
        downloadUsersButton.addEventListener('click', function (e) {
            e.preventDefault();
            if (downloadUsersButton.classList.contains('disabled')) {
                return;
            }
            if (!voorodakAdminAjax.users_export_url) {
                alert('آدرس خروجی کاربران در دسترس نیست. صفحه را تازه‌سازی کنید.');
                return;
            }

            // The server streams CSV directly, which avoids buffering the full
            // user list in PHP memory and then duplicating it inside a JSON response.
            window.location.assign(voorodakAdminAjax.users_export_url);
        });
    }

    const testPhoneButton = voorodakAdminQuery('#test_phone_submit');
    if (testPhoneButton) {
        testPhoneButton.addEventListener('click', function (e) {
            e.preventDefault();
            if (testPhoneButton.classList.contains('disabled')) {
                return;
            }
            const btnText = testPhoneButton.innerHTML;
            const phone = voorodakAdminQuery('#test_phone_number');

            testPhoneButton.innerHTML = 'در حال ارسال';
            testPhoneButton.classList.add('disabled');

            voorodakAdminPost(
                {
                    action: 'submit_test_phone',
                    security: voorodakAdminAjax.security,
                    phone: phone ? phone.value : ''
                },
                function (response) {
                    testPhoneButton.innerHTML = btnText;
                    testPhoneButton.classList.remove('disabled');
                    voorodakAdminShow('#test_phone_result');
                    voorodakAdminSetText('#test_phone_result td', response.data);
                },
                function () {
                    testPhoneButton.innerHTML = btnText;
                    testPhoneButton.classList.remove('disabled');
                    voorodakAdminShow('#test_phone_result');
                    voorodakAdminSetText('#test_phone_result td', 'خطا در ارسال درخواست');
                }
            );
        });
    }

    function voorodakAdminToggleRows(toggle) {
        const checked = toggle.checked;
        let rows = [];
        let next = toggle.closest('tr') ? toggle.closest('tr').nextElementSibling : null;

        while (next && rows.length < 2) {
            if (next.tagName && next.tagName.toLowerCase() === 'tr') {
                rows.push(next);
            }
            next = next.nextElementSibling;
        }

        if (rows[1] && rows[1].classList.contains('ignore-second')) {
            rows = rows.slice(0, 1);
        }

        rows.forEach(function (row) {
            row.style.display = checked ? '' : 'none';
        });
    }

    voorodakAdminQueryAll('.v-toggle').forEach(function (toggle) {
        toggle.addEventListener('change', function () {
            voorodakAdminToggleRows(toggle);
        });
        voorodakAdminToggleRows(toggle);
    });

    voorodakAdminQueryAll('.sms-notifications h3').forEach(function (heading) {
        heading.addEventListener('click', function () {
            const target = heading.nextElementSibling;
            if (target) {
                target.style.display = target.style.display === 'none' || getComputedStyle(target).display === 'none' ? 'block' : 'none';
            }
            heading.classList.toggle('activate');
        });
    });

    function checkFieldsSmart() {
        const gatewaySelect = voorodakAdminQuery('.voorodak__gateway select');
        const selectedValue = gatewaySelect ? gatewaySelect.value : '';
        const usernameLabel = voorodakAdminQuery('.voorodak__username th');

        if (selectedValue.indexOf('pattern') >= 0) {
            voorodakAdminHide('.voorodak__message');
            voorodakAdminShow('.voorodak__pattern');
        } else {
            voorodakAdminShow('.voorodak__message');
            voorodakAdminHide('.voorodak__pattern');
        }

        if (usernameLabel) {
            usernameLabel.textContent = 'نام کاربری سامانه';
        }
        voorodakAdminShow('.voorodak__password');
        voorodakAdminShow('.voorodak__from');

        const apiKeyNoFromGateways = [
            'kavenegar_pattern',
            'payamresan_pattern',
            'ghasedak_pattern',
            'smsir_pattern',
            'rahpayam_pattern',
            'ippanelnew_pattern',
            'modirpayamak_pattern'
        ];

        const noFromGateways = [
            'farapayamak_pattern',
            'payamito_pattern',
            'melipayamak_pattern',
            'melipayamakrest_pattern'
        ];

        const apiKeyWithFromGateways = [
            'farazsmsnew_pattern',
            'panelchi_pattern',
            'sabanovin',
            'raygansms_pattern'
        ];

        if (apiKeyNoFromGateways.indexOf(selectedValue) >= 0) {
            if (usernameLabel) {
                usernameLabel.textContent = 'کلید API';
            }
            voorodakAdminHide('.voorodak__password');
            voorodakAdminHide('.voorodak__from');
        } else if (noFromGateways.indexOf(selectedValue) >= 0) {
            voorodakAdminHide('.voorodak__from');
        } else if (apiKeyWithFromGateways.indexOf(selectedValue) >= 0) {
            if (usernameLabel) {
                usernameLabel.textContent = 'کلید API';
            }
            voorodakAdminHide('.voorodak__password');
            voorodakAdminShow('.voorodak__from');
        }

        const redirectChecked = voorodakAdminQuery('.voorodak__backurl input[type=radio]:checked');
        if (redirectChecked && redirectChecked.value === 'custom') {
            voorodakAdminShow('.voorodak__backurl-custom');
        } else {
            voorodakAdminHide('.voorodak__backurl-custom');
        }
    }

    checkFieldsSmart();
    const gatewayRow = voorodakAdminQuery('.voorodak__gateway');
    if (gatewayRow) {
        gatewayRow.addEventListener('change', checkFieldsSmart);
    }
    voorodakAdminQueryAll('.voorodak__backurl input[type=radio]').forEach(function (radio) {
        radio.addEventListener('change', checkFieldsSmart);
    });

    const adminTabs = voorodakAdminQueryAll('.voorodak__body-tab a[role="tab"]');
    const settingsSearch = voorodakAdminQuery('#voorodak-settings-search');
    const toolbarHint = voorodakAdminQuery('.voorodak-admin-toolbar__hint');
    const settingsForm = voorodakAdminQuery('.voorodak__body-main form');

    function getActiveAdminPanel() {
        const activeTab = voorodakAdminQuery('.voorodak__body-tab a.active');
        return activeTab ? voorodakAdminQuery(activeTab.getAttribute('href')) : null;
    }

    function resetSettingsSearch() {
        if (settingsSearch) {
            settingsSearch.value = '';
        }
        voorodakAdminQueryAll('.voorodak-search-hidden').forEach(function (element) {
            element.classList.remove('voorodak-search-hidden');
        });
        const emptyState = voorodakAdminQuery('.voorodak-search-empty');
        if (emptyState) {
            emptyState.remove();
        }
    }

    function activateAdminTab(tab, updateHash) {
        if (!tab) return;

        adminTabs.forEach(function (item) {
            const selected = item === tab;
            item.classList.toggle('active', selected);
            item.setAttribute('aria-selected', selected ? 'true' : 'false');
            item.setAttribute('tabindex', selected ? '0' : '-1');

            const panel = voorodakAdminQuery(item.getAttribute('href'));
            if (panel) {
                panel.style.display = selected ? 'block' : 'none';
                panel.setAttribute('aria-hidden', selected ? 'false' : 'true');
            }
        });

        resetSettingsSearch();

        if (updateHash && window.history && window.history.replaceState) {
            window.history.replaceState(null, '', tab.getAttribute('href'));
        }
    }

    adminTabs.forEach(function (tab, index) {
        tab.addEventListener('click', function (e) {
            e.preventDefault();
            activateAdminTab(tab, true);
        });

        tab.addEventListener('keydown', function (e) {
            if (!['ArrowRight', 'ArrowLeft', 'Home', 'End'].includes(e.key)) return;
            e.preventDefault();

            let nextIndex = index;
            if (e.key === 'ArrowRight') nextIndex = (index - 1 + adminTabs.length) % adminTabs.length;
            if (e.key === 'ArrowLeft') nextIndex = (index + 1) % adminTabs.length;
            if (e.key === 'Home') nextIndex = 0;
            if (e.key === 'End') nextIndex = adminTabs.length - 1;

            adminTabs[nextIndex].focus();
            activateAdminTab(adminTabs[nextIndex], true);
        });
    });

    const requestedHash = window.location.hash;
    const requestedTab = requestedHash
        ? adminTabs.find(function (tab) { return tab.getAttribute('href') === requestedHash; })
        : null;
    activateAdminTab(requestedTab || adminTabs[0], false);

    if (settingsSearch) {
        settingsSearch.addEventListener('input', function () {
            const panel = getActiveAdminPanel();
            if (!panel) return;

            const query = settingsSearch.value.trim().toLocaleLowerCase('fa');
            voorodakAdminQueryAll('.voorodak-search-hidden', panel).forEach(function (element) {
                element.classList.remove('voorodak-search-hidden');
            });

            const previousEmpty = voorodakAdminQuery('.voorodak-search-empty', panel);
            if (previousEmpty) previousEmpty.remove();
            if (!query) return;

            const candidates = voorodakAdminQueryAll('.form-table tr, .doc-box', panel);
            let matches = 0;

            candidates.forEach(function (element) {
                const text = (element.textContent || '').toLocaleLowerCase('fa');
                const matched = text.indexOf(query) !== -1;
                element.classList.toggle('voorodak-search-hidden', !matched);
                if (matched) matches += 1;
            });

            if (matches === 0) {
                const empty = document.createElement('div');
                empty.className = 'voorodak-search-empty';
                empty.textContent = 'موردی مطابق جستجوی شما در این بخش پیدا نشد.';
                panel.prepend(empty);
            }
        });
    }

    if (settingsForm && toolbarHint) {
        let dirty = false;
        const markDirty = function () {
            if (dirty) return;
            dirty = true;
            toolbarHint.textContent = 'تغییرات ذخیره‌نشده دارید.';
            toolbarHint.classList.add('is-dirty');
        };

        settingsForm.addEventListener('input', markDirty);
        settingsForm.addEventListener('change', markDirty);
        settingsForm.addEventListener('submit', function () {
            dirty = false;
            toolbarHint.textContent = 'در حال ذخیره تغییرات…';
            toolbarHint.classList.remove('is-dirty');
        });
    }

    if (window.jQuery && typeof window.jQuery.fn.wpColorPicker === 'function') {
        window.jQuery('.voorodak__color-picker').wpColorPicker();
    }

    function initMediaUploader(buttonSelector, previewSelector, inputSelector, removeButtonSelector) {
        const button = voorodakAdminQuery(buttonSelector);
        const removeButton = voorodakAdminQuery(removeButtonSelector);
        let mediaUploader;

        if (button) {
            button.addEventListener('click', function (e) {
                e.preventDefault();
                if (!window.wp || !wp.media) {
                    return;
                }

                if (mediaUploader) {
                    mediaUploader.open();
                    return;
                }

                mediaUploader = wp.media({
                    title: 'انتخاب تصویر پس زمینه',
                    button: {
                        text: 'انتخاب تصویر'
                    },
                    multiple: false
                });

                mediaUploader.on('select', function () {
                    var attachment = mediaUploader.state().get('selection').first().toJSON();
                    voorodakAdminSetHtml(previewSelector, '<img src="' + attachment.url + '" style="max-width: 200px; max-height: 200px;" />');
                    voorodakAdminSetValue(inputSelector, attachment.url);
                    voorodakAdminShow(removeButtonSelector);
                });

                mediaUploader.open();
            });
        }

        if (removeButton) {
            removeButton.addEventListener('click', function () {
                voorodakAdminSetHtml(previewSelector, '');
                voorodakAdminSetValue(inputSelector, '');
                removeButton.style.display = 'none';
            });
        }
    }

    initMediaUploader('#voorodak__logo-upload-button', '#voorodak__logo-preview', 'input[name="voorodak_options[logo]"]', '#voorodak__logo-upload-remove');
    initMediaUploader('#voorodak__cover-upload-button', '#voorodak__cover-preview', 'input[name="voorodak_options[cover]"]', '#voorodak__cover-upload-remove');

    document.querySelectorAll('.multi-select-builder').forEach(function (container) {
        const hiddenName = container.dataset.name || 'items';
        const placeholder = container.dataset.placeholder || 'انتخاب کنید';
        const optionsRaw = container.dataset.options || '';
        const defaultValues = container.dataset.default ? container.dataset.default.split('-') : [];

        const options = optionsRaw.split(',').filter(Boolean).map(function (item) {
            const parts = item.split(':');
            return {
                value: (parts[0] || '').trim(),
                label: (parts[1] || parts[0] || '').trim()
            };
        }).filter(function (item) {
            return item.value;
        });

        let selected = defaultValues.filter(function (value) {
            return options.some(function (option) {
                return option.value === value;
            });
        });

        container.innerHTML = [
            '<div class="msb-wrap">',
            '<select class="msb-select"><option value="">' + placeholder + '</option></select>',
            '<div class="msb-items"></div>',
            '<input type="hidden" name="' + hiddenName + '" class="msb-hidden">',
            '</div>'
        ].join('');

        const select = container.querySelector('.msb-select');
        const itemsBox = container.querySelector('.msb-items');
        const hiddenInput = container.querySelector('.msb-hidden');

        function updateHidden() {
            hiddenInput.value = selected.join('-');
        }

        function renderSelectOptions() {
            select.innerHTML = '<option value="">' + placeholder + '</option>' + options
                .filter(function (option) {
                    return selected.indexOf(option.value) === -1;
                })
                .map(function (option) {
                    return '<option value="' + option.value + '">' + option.label + '</option>';
                }).join('');
        }

        function renderItems() {
            itemsBox.innerHTML = '';

            selected.forEach(function (value) {
                const option = options.find(function (item) {
                    return item.value === value;
                });

                if (!option) return;

                const item = document.createElement('div');
                item.className = 'msb-item';
                item.innerHTML = '<span>' + value + '</span><button type="button">×</button>';

                item.querySelector('button').addEventListener('click', function () {
                    selected = selected.filter(function (selectedValue) {
                        return selectedValue !== value;
                    });
                    renderSelectOptions();
                    renderItems();
                    updateHidden();
                });

                itemsBox.appendChild(item);
            });
        }

        select.addEventListener('change', function () {
            const selectedValue = select.value;

            if (!selectedValue || selected.indexOf(selectedValue) >= 0) {
                return;
            }

            selected.push(selectedValue);
            renderSelectOptions();
            renderItems();
            updateHidden();
            select.value = '';
        });

        renderSelectOptions();
        renderItems();
        updateHidden();
    });
});
