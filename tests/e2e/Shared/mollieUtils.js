const {sharedUrl: {mollieSettingsTab}} = require('../Shared/sharedUrl');
const {loginAdmin} = require("./wpUtils");
/**
 * @param {import('@playwright/test').Page} page
 */
const setOrderAPI = async (page) => {
    await loginAdmin(page)
    await page.goto(mollieSettingsTab + '&section=advanced');
    await page.selectOption('select#mollie-payments-for-woocommerce_api_switch', 'order')
    await Promise.all([
        page.waitForNavigation(),
        page.locator('text=Save changes').click()
    ]);
}

/**
 * @param {import('@playwright/test').Page} page
 */
const setPaymentAPI = async (page) => {
    await page.goto(mollieSettingsTab + '&section=advanced');
    await page.selectOption('select#mollie-payments-for-woocommerce_api_switch', 'payment')
    await Promise.all([
        page.waitForNavigation(),
        page.locator('text=Save changes').click()
    ]);
}

/**
 * @param {import('@playwright/test').Page} page
 * @param status
 */
const markStatusInMollie = async (page, status) =>{
    const mollieHeader = await page.innerText('.header__info');
    const mollieOrder = mollieHeader.substring(6, mollieHeader.length)
    await page.locator('text=' + status).click();
    await page.locator('text=Continue').click();
    return mollieOrder;
}

/**
 * @param {import('@playwright/test').Page} page
 */
const insertAPIKeys = async (page) =>{
    await page.goto(mollieSettingsTab);
    await page.locator(`input[name="mollie-payments-for-woocommerce_live_api_key"]`).fill(process.env.MOLLIE_LIVE_API_KEY);
    await page.locator(`input[name="mollie-payments-for-woocommerce_test_mode_enabled"]`).check();
    await page.locator(`input[name="mollie-payments-for-woocommerce_test_api_key"]`).fill(process.env.MOLLIE_TEST_API_KEY);
    await Promise.all([
        page.waitForNavigation(),
        page.locator('text=Save changes').click()
    ]);
}

/**
 * @param {import('@playwright/test').Page} page
 */
const resetSettings = async (page) => {
    await page.goto(mollieSettingsTab + '&section=advanced');
    await Promise.all([
        page.waitForNavigation(),
        await page.locator('text=clear now').click()
    ]);
}

module.exports = {setOrderAPI, setPaymentAPI, markStatusInMollie, insertAPIKeys, resetSettings};
