<?php

namespace OroCRM\Bundle\TestsBundle\Tests\Selenium;

use Oro\Bundle\TestFrameworkBundle\Pages\Objects\Login;
use Oro\Bundle\TestFrameworkBundle\Pages\Page;
use Oro\Bundle\TestFrameworkBundle\Test\Selenium2TestCase;

class AclTest extends Selenium2TestCase
{
    protected $coverageScriptUrl = PHPUNIT_TESTSUITE_EXTENSION_SELENIUM_TESTS_URL_COVERAGE;

    protected $newRole = array('ROLE_NAME' => 'NEW_ROLE_', 'LABEL' => 'Role_label_');

    protected function setUp()
    {
        $this->setHost(PHPUNIT_TESTSUITE_EXTENSION_SELENIUM_HOST);
        $this->setPort(intval(PHPUNIT_TESTSUITE_EXTENSION_SELENIUM_PORT));
        $this->setBrowser(PHPUNIT_TESTSUITE_EXTENSION_SELENIUM2_BROWSER);
        $this->setBrowserUrl(PHPUNIT_TESTSUITE_EXTENSION_SELENIUM_TESTS_URL);
    }

    protected function tearDown()
    {
        $this->cookie()->clear();
    }

    public function testCreateRole()
    {
        $randomPrefix = mt_rand();
        $login = new Login($this);
        $login->setUsername(PHPUNIT_TESTSUITE_EXTENSION_SELENIUM_LOGIN)
            ->setPassword(PHPUNIT_TESTSUITE_EXTENSION_SELENIUM_PASS)
            ->submit()
            ->openRoles()
            ->add()
            ->setLabel($this->newRole['LABEL'] . $randomPrefix)
            ->setOwner('Main')
            ->setEntity('Contact Group', array('Create', 'Edit', 'Delete', 'View', 'Assign'), 'System')
            ->setEntity('Contact', array('Create', 'Edit', 'Delete', 'View', 'Assign'), 'System')
            ->setEntity('Account', array('Create', 'Edit', 'Delete', 'View', 'Assign'), 'System')
            ->save()
            ->assertMessage('Role saved');

        return ($this->newRole['LABEL'] . $randomPrefix);
    }

    /**
     * @param $roleName
     * @depends testCreateRole
     * @return string
     */
    public function testCreateUser($roleName)
    {
        $username = 'User_'.mt_rand();

        $login = new Login($this);
        $login->setUsername(PHPUNIT_TESTSUITE_EXTENSION_SELENIUM_LOGIN)
            ->setPassword(PHPUNIT_TESTSUITE_EXTENSION_SELENIUM_PASS)
            ->submit()
            ->openUsers()
            ->add()
            ->assertTitle('Create User - Users - Users Management - System')
            ->setUsername($username)
            ->setOwner('Main')
            ->enable()
            ->setFirstpassword('123123q')
            ->setSecondpassword('123123q')
            ->setFirstName('First_'.$username)
            ->setLastName('Last_'.$username)
            ->setEmail($username.'@mail.com')
            ->setRoles(array($roleName))
            ->save()
            ->assertMessage('User saved')
            ->toGrid()
            ->close()
            ->assertTitle('Users - Users Management - System');

        return $username;
    }

    /**
     * @param $username
     * @depends testCreateUser
     */
    public function testUserAccess($username)
    {
        $login = new Login($this);
        $login->setUsername($username)
            ->setPassword('123123q')
            ->submit();
        $login->assertElementNotPresent(
            "//div[@id='main-menu']/ul/li/a[normalize-space(.) = 'System']",
            'Element present so ACL for Users do not work'
        );
        $login->assertElementNotPresent("//div[@id='search-div']", 'Element present so ACL for Search do not work');
        $login->byXPath("//ul[@class='nav pull-right user-menu']//a[@class='dropdown-toggle']")->click();
    }

    /**
     * @param $username
     * @depends testCreateUser
     */
    public function testUserAccessDirectUrl($username)
    {
        $login = new Login($this);
        $login->setUsername($username)
            ->setPassword('123123q')
            ->submit()
            ->openUsers()
            ->assertTitle('403 - Forbidden')
            ->openRoles()
            ->assertTitle('403 - Forbidden')
            ->openGroups()
            ->assertTitle('403 - Forbidden')
            ->openDataAudit()
            ->assertTitle('403 - Forbidden');
    }

    /**
     * @param $roleName
     * @depends testCreateRole
     */
    public function testEditRole($roleName)
    {
        $login = new Login($this);
        $login->setUsername(PHPUNIT_TESTSUITE_EXTENSION_SELENIUM_LOGIN)
            ->setPassword(PHPUNIT_TESTSUITE_EXTENSION_SELENIUM_PASS)
            ->submit()
            ->openRoles()
            ->filterBy('Label', $roleName)
            ->open(array($roleName))
            ->setEntity('Account', array('Create', 'Edit', 'Delete', 'Assign'), 'None')
            ->setEntity('Contact Group', array('Create', 'Edit', 'Delete', 'Assign'), 'None')
            ->setEntity('Contact', array('Create', 'Edit', 'Delete', 'Assign'), 'None')
            //->setEntity('User', array('View', 'Edit'))
            ->save()
            ->assertMessage('Role saved');
    }

    /**
     * @param $username
     * @depends testCreateUser
     * @depends testEditRole
     */
    public function testViewAccountsContacts($username)
    {
        $login = new Login($this);
        $login->setUsername($username)
            ->setPassword('123123q')
            ->submit()
            ->openAccounts()
            ->assertTitle('Accounts - Customers')
            ->assertElementNotPresent("//div[@class='container-fluid']//a[@title='Create account']")
            ->openContacts()
            ->assertTitle('Contacts - Customers')
            ->assertElementNotPresent("//div[@class='container-fluid']//a[@title='Create contact']")
            ->openContactGroups()
            ->assertTitle('Contact Groups - Customers')
            ->assertElementNotPresent("//div[@class='container-fluid']//a[@title='Create contact group']")
            ->openAclCheck()
            ->assertAcl('account/create')
            ->assertAcl('contact/create')
            ->assertAcl('contact/group/create')
            ->assertAcl('contact/group/create');
    }

    /**
     * @param $username
     * @depends testCreateUser
     * @depends testEditRole
     */
    public function testEditUserProfile($username)
    {
        $login = new Login($this);
        $login->setUsername($username)
            ->setPassword('123123q')
            ->submit()
            ->openUser()
            ->viewInfo($username)
            ->checkRoleSelector();
    }
}
