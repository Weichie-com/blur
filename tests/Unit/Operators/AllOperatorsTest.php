<?php

declare(strict_types=1);

namespace Weichie\Blur\Tests\Unit\Operators;

use PHPUnit\Framework\TestCase;
use Weichie\Blur\Anonymizer\Operators\ReplaceOperator;
use Weichie\Blur\Anonymizer\Operators\RedactOperator;
use Weichie\Blur\Anonymizer\Operators\MaskOperator;
use Weichie\Blur\Anonymizer\Operators\HashOperator;
use Weichie\Blur\Anonymizer\Operators\EncryptOperator;
use Weichie\Blur\Anonymizer\Operators\DecryptOperator;

class AllOperatorsTest extends TestCase
{
    // ============ Replace Operator ============

    public function testReplaceOperator(): void
    {
        $operator = new ReplaceOperator();

        $this->assertEquals('replace', $operator->getName());

        $result = $operator->operate('test@example.com', ['new_value' => '[EMAIL]']);
        $this->assertEquals('[EMAIL]', $result);
    }

    public function testReplaceOperatorMissingParameter(): void
    {
        $operator = new ReplaceOperator();

        $this->expectException(\InvalidArgumentException::class);
        $operator->validateParams([]);
    }

    // ============ Redact Operator ============

    public function testRedactOperator(): void
    {
        $operator = new RedactOperator();

        $this->assertEquals('redact', $operator->getName());

        $result = $operator->operate('sensitive data');
        $this->assertEquals('', $result);
    }

    public function testRedactOperatorNoParams(): void
    {
        $operator = new RedactOperator();

        // Should not throw
        $operator->validateParams([]);
        $this->assertTrue(true);
    }

    // ============ Mask Operator ============

    public function testMaskOperatorAll(): void
    {
        $operator = new MaskOperator();

        // Mask all characters
        $result = $operator->operate('123456789', ['chars_to_mask' => -1]);
        $this->assertEquals('*********', $result);
    }

    public function testMaskOperatorFromStart(): void
    {
        $operator = new MaskOperator();

        // Mask first 6 characters
        $result = $operator->operate('123456789', ['chars_to_mask' => 6, 'from_end' => false]);
        $this->assertEquals('******789', $result);
    }

    public function testMaskOperatorFromEnd(): void
    {
        $operator = new MaskOperator();

        // Mask last 4 characters
        $result = $operator->operate('123456789', ['chars_to_mask' => 4, 'from_end' => true]);
        $this->assertEquals('12345****', $result);
    }

    public function testMaskOperatorCustomChar(): void
    {
        $operator = new MaskOperator();

        $result = $operator->operate('123456789', [
            'masking_char' => '#',
            'chars_to_mask' => 5
        ]);
        $this->assertEquals('#####6789', $result);
    }

    public function testMaskOperatorUTF8(): void
    {
        $operator = new MaskOperator();

        $result = $operator->operate('café', ['chars_to_mask' => 2]);
        $this->assertEquals('**fé', $result);
    }

    public function testMaskOperatorInvalidMaskingChar(): void
    {
        $operator = new MaskOperator();

        $this->expectException(\InvalidArgumentException::class);
        $operator->validateParams(['masking_char' => 'ab']); // More than 1 character
    }

    public function testMaskOperatorInvalidCharsToMask(): void
    {
        $operator = new MaskOperator();

        $this->expectException(\InvalidArgumentException::class);
        $operator->validateParams(['chars_to_mask' => -2]); // Less than -1
    }

    // ============ Hash Operator ============

    public function testHashOperatorSHA256(): void
    {
        $operator = new HashOperator();

        $result = $operator->operate('test', ['algorithm' => 'sha256']);
        $this->assertEquals(64, strlen($result)); // SHA-256 produces 64 hex chars
        $this->assertEquals(hash('sha256', 'test'), $result);
    }

    public function testHashOperatorSHA512(): void
    {
        $operator = new HashOperator();

        $result = $operator->operate('test', ['algorithm' => 'sha512']);
        $this->assertEquals(128, strlen($result)); // SHA-512 produces 128 hex chars
        $this->assertEquals(hash('sha512', 'test'), $result);
    }

    public function testHashOperatorDefaultAlgorithm(): void
    {
        $operator = new HashOperator();

        $result = $operator->operate('test');
        $this->assertEquals(64, strlen($result)); // Default is SHA-256
    }

    public function testHashOperatorDeterministic(): void
    {
        $operator = new HashOperator();

        $result1 = $operator->operate('same-input');
        $result2 = $operator->operate('same-input');

        $this->assertEquals($result1, $result2, 'Hash should be deterministic');
    }

    public function testHashOperatorInvalidAlgorithm(): void
    {
        $operator = new HashOperator();

        $this->expectException(\InvalidArgumentException::class);
        $operator->validateParams(['algorithm' => 'md5']);
    }

    // ============ Encrypt/Decrypt Operators ============

    public function testEncryptDecryptRoundtrip(): void
    {
        $encryptOp = new EncryptOperator();
        $decryptOp = new DecryptOperator();

        $original = 'sensitive data';
        $key = 'my-secret-key-2024';

        // Encrypt
        $encrypted = $encryptOp->operate($original, ['key' => $key]);

        // Should be different from original
        $this->assertNotEquals($original, $encrypted);

        // Should be base64 encoded
        $this->assertNotFalse(base64_decode($encrypted, true));

        // Decrypt
        $decrypted = $decryptOp->operate($encrypted, ['key' => $key]);

        // Should match original
        $this->assertEquals($original, $decrypted);
    }

    public function testEncryptOperatorDifferentOutputs(): void
    {
        $operator = new EncryptOperator();

        $key = 'my-secret-key';

        // Same input should produce different outputs (random IV)
        $result1 = $operator->operate('test', ['key' => $key]);
        $result2 = $operator->operate('test', ['key' => $key]);

        $this->assertNotEquals($result1, $result2, 'Encryption should use random IV');
    }

    public function testDecryptOperatorWrongKey(): void
    {
        $encryptOp = new EncryptOperator();
        $decryptOp = new DecryptOperator();

        $encrypted = $encryptOp->operate('test', ['key' => 'correct-key']);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Decryption failed');
        $decryptOp->operate($encrypted, ['key' => 'wrong-key']);
    }

    public function testDecryptOperatorInvalidData(): void
    {
        $operator = new DecryptOperator();

        $this->expectException(\RuntimeException::class);
        $operator->operate('invalid-base64-data', ['key' => 'some-key']);
    }

    public function testEncryptOperatorMissingKey(): void
    {
        $operator = new EncryptOperator();

        $this->expectException(\InvalidArgumentException::class);
        $operator->validateParams([]);
    }

    public function testEncryptOperatorEmptyKey(): void
    {
        $operator = new EncryptOperator();

        $this->expectException(\InvalidArgumentException::class);
        $operator->validateParams(['key' => '']);
    }

    public function testEncryptDecryptUTF8(): void
    {
        $encryptOp = new EncryptOperator();
        $decryptOp = new DecryptOperator();

        $original = 'Café François';
        $key = 'secret-key';

        $encrypted = $encryptOp->operate($original, ['key' => $key]);
        $decrypted = $decryptOp->operate($encrypted, ['key' => $key]);

        $this->assertEquals($original, $decrypted);
    }

    public function testEncryptDecryptEmptyString(): void
    {
        $encryptOp = new EncryptOperator();
        $decryptOp = new DecryptOperator();

        $original = '';
        $key = 'secret-key';

        $encrypted = $encryptOp->operate($original, ['key' => $key]);
        $decrypted = $decryptOp->operate($encrypted, ['key' => $key]);

        $this->assertEquals($original, $decrypted);
    }

    public function testEncryptDecryptLongText(): void
    {
        $encryptOp = new EncryptOperator();
        $decryptOp = new DecryptOperator();

        $original = str_repeat('This is a long text with UTF-8 characters like café and naïve. ', 100);
        $key = 'secret-key';

        $encrypted = $encryptOp->operate($original, ['key' => $key]);
        $decrypted = $decryptOp->operate($encrypted, ['key' => $key]);

        $this->assertEquals($original, $decrypted);
    }
}
