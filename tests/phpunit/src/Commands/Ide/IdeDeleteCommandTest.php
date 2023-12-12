<?php

declare(strict_types = 1);

namespace Acquia\Cli\Tests\Commands\Ide;

use Acquia\Cli\Command\CommandBase;
use Acquia\Cli\Command\Ide\IdeDeleteCommand;
use Acquia\Cli\Command\Ssh\SshKeyDeleteCommand;
use Acquia\Cli\Tests\CommandTestBase;
use AcquiaCloudApi\Response\IdeResponse;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @property IdeDeleteCommand $command
 */
class IdeDeleteCommandTest extends CommandTestBase {

  /**
   * This method is called before each test.
   */
  public function setUp(OutputInterface $output = NULL): void {
    parent::setUp();
    $this->getCommandTester();
    $this->application->addCommands([
      $this->injectCommand(SshKeyDeleteCommand::class),
    ]);
  }

  protected function createCommand(): CommandBase {
    return $this->injectCommand(IdeDeleteCommand::class);
  }

  /**
   * @group brokenProphecy
   */
  public function testIdeDeleteCommand(): void {
    $applications = $this->mockRequest('getApplications');
    $this->mockRequest('getApplicationByUuid', $applications[0]->uuid);
    $ides = $this->mockRequest('getApplicationIdes', $applications[0]->uuid);
    $this->mockRequest('deleteIde', $ides[0]->uuid, NULL, 'De-provisioning IDE');
    $ideGetResponse = $this->mockRequest('getIde', $ides[0]->uuid);
    $ide = new IdeResponse((object) $ideGetResponse);
    $sshKeyGetResponse = $this->mockListSshKeysRequestWithIdeKey($ide);

    $this->mockDeleteSshKeyRequest($sshKeyGetResponse->{'_embedded'}->items[0]->uuid);

    $inputs = [
      // Would you like Acquia CLI to search for a Cloud application that matches your local git config?
      'n',
      // Select the application for which you'd like to create a new IDE.
      0,
      // Would you like to link the project at ... ?
      'y',
      // Select the IDE you'd like to delete:
      0,
      // Would you like to delete the SSH key associated with this IDE from your Cloud Platform account?
      'y',
    ];

    $this->executeCommand([], $inputs);

    // Assert.

    $output = $this->getDisplay();
    $this->assertStringContainsString('The Cloud IDE is being deleted.', $output);
  }

}
