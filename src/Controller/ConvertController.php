<?php

namespace App\Umanitoba\Kakadu\Controller;

use GuzzleHttp\Psr7\StreamWrapper;
use Islandora\Crayfish\Commons\CmdExecuteService;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\Routing\Annotation\Route;

class ConvertController extends AbstractController
{
    private CmdExecuteService $cmd;
    private LoggerInterface $logger;
    private array $formats;
    private string $default_format;
    private string $kakadu_cmd;
    private string $temp_dir;
    private string $identify_cmd;
    private string $convert_cmd;

    public function __construct(
        CmdExecuteService $cmd,
        LoggerInterface $logger,
        array $formats,
        string $default_format,
        string $kakadu_exec,
        string $temporary_directory,
        string $identify_exec,
        string $convert_exec
    ) {
        $this->cmd = $cmd;
        $this->logger = $logger;
        $this->formats = $formats;
        $this->default_format = $default_format;
        $this->kakadu_cmd = $kakadu_exec;
        $this->temp_dir = $temporary_directory;
        $this->identify_cmd = $identify_exec;
        $this->convert_cmd = $convert_exec;
        if (!is_dir($this->temp_dir)) {
            $this->logger->error('Temporary directory does not exist.');
            throw new \RuntimeException('Temporary directory does not exist.');
        }
        if (!is_executable($this->kakadu_cmd)) {
            $this->logger->error('Kakadu executable does not exist or is not executable.');
            throw new \RuntimeException('Kakadu executable does not exist or is not executable.');
        }
        if (!is_executable($this->identify_cmd)) {
            $this->logger->error('ImageMagick identify executable does not exist or is not executable.');
            throw new \RuntimeException('ImageMagick identify executable does not exist or is not executable.');
        }
        if (!is_executable($this->convert_cmd)) {
            $this->logger->error('ImageMagick convert executable does not exist or is not executable.');
            throw new \RuntimeException('ImageMagick convert executable does not exist or is not executable.');
        }
    }

    /**
     * Convert a Fedora resource to a different format.
     *
     * @param Request $request
     *   The request.
     *
     * @return StreamedResponse|Response
     *   The response.
     */
    #[Route('/convert', name: 'convert', methods: ['GET'])]
    public function convert(Request $request): StreamedResponse|Response
    {
        $this->logger->info('Convert request.');

        $fedora_resource = $request->attributes->get('fedora_resource');
        $source_content_type = array_key_exists('Content-Type', $fedora_resource->getHeaders()) ?
            $fedora_resource->getHeaders()['Content-Type'] : '';
        if (is_array($source_content_type)) {
            $source_content_type = array_pop($source_content_type);
        }
        $this->logger->debug('source content type:', ['source_content_type' => $source_content_type]);
        // Get image as a resource and put it in a tempfile.
        $source_file = $this->getTempFile() . '.' . $this->getExtension($source_content_type);
        $this->logger->debug('source file:', ['source_file' => $source_file]);
        file_put_contents($source_file, StreamWrapper::getResource($fedora_resource->getBody()));
        if ($this->isCompressed($source_file)) {
            // Decompress the file.
            $this->logger->info('Decompressing file.');
            $source_file = $this->unCompressFile($source_file);
        }

        // Arguments to image convert command are sent as a custom header
        $args = $request->headers->get('X-Islandora-Args');
        $this->logger->debug("X-Islandora-Args:", ['args' => $args]);

        // Find the correct image type to return
        $content_type = null;
        $content_types = $request->getAcceptableContentTypes();
        $this->logger->debug('Content Types:', is_array($args) ? $args : []);
        foreach ($content_types as $type) {
            if (in_array($type, $this->formats)) {
                $content_type = $type;
                break;
            }
        }
        if ($content_type === null) {
            $content_type = $this->default_format;
            $this->logger->info('Falling back to default content type');
        }
        $this->logger->debug('Content Type Chosen:', ['type' => $content_type]);

        // Build arguments
        $extension = $this->getExtension($content_type);

        // Default args
        if (empty($args)) {
            $args = "Clayers=1 Clevels=7 " .
                "Cprecincts={256,256},{256,256},{256,256},{256,256},{256,256},{256,256},{256,256},{128,128} Corder=RPCL Cblk={64,64}" .
                " Cuse_sop=yes ORGgen_plt=yes ORGtparts=R ORGgen_tlm=8";
        }
        $output_file = $this->getTempFile() . '.' . $extension;

        $cmd_string = "$this->kakadu_cmd -i $source_file -o $output_file $args";

        $this->logger->info('Kakadu Command:', ['cmd' => $cmd_string]);

        // Return response.
        try {

            $result = $this->cmd->execute($cmd_string, null);
            $this->logger->debug('Kakadu Result:', ['result' => $result]);
            $headers = [
                'Content-Type' => $content_type,
            ];
            $size = filesize($output_file);
            if ($size === false) {
                $t = stat($output_file);
                if ($t !== false) {
                    $size = $t['size'];
                }
            }
            if ($size !== false) {
                $headers['Content-Length'] = $size;
            }

            return new StreamedResponse(
                function () use ($output_file) {
                    $handle = fopen($output_file, 'rb');
                    while (!feof($handle)) {
                        echo fread($handle, 8192);
                        flush();
                    }
                    fclose($handle);
                },
                200,
                $headers
            );
        } catch (\RuntimeException $e) {
            $this->logger->error("RuntimeException:", ['exception' => $e]);
            return new Response($e->getMessage(), 500);
        } finally {
            @unlink($source_file);
        }
    }

  /**
   * @return string The path to a temporary file.
   */
    private function getTempFile(): string
    {
        $name = tempnam($this->temp_dir, 'kakadu');
        if ($name === false) {
            throw new \RuntimeException('Failed to create temporary file.');
        }
        @unlink($name);
        return $name;
    }

    /**
     * Get the extension for a given content type.
     *
     * @param string $content_type
     *   The content type.
     *
     * @return string
     *   The extension.
     */
    private function getExtension(string $content_type): string
    {
        $exploded = explode('/', $content_type, 2);
        $type = count($exploded) == 2 ? $exploded[1] : $exploded[0];
        return match ($type) {
            'jpeg' => 'jpg',
            'tiff' => 'tiff',
            default => $type,
        };
    }

    /**
     * Check if a file is compressed.
     *
     * @param string $filepath
     *   The file path.
     *
     * @return bool
     *   True if the file is compressed, false otherwise.
     */
    private function isCompressed(string $filepath): bool
    {
        $cmd_string = "$this->identify_cmd -format '%C' $filepath";
        $this->logger->debug('Identify Command:', ['cmd' => $cmd_string]);
        $result = $this->cmd->execute($cmd_string, null);
        $this->logger->debug('Identify Result:', ['result' => $result]);
        return $result != 'None';
    }

    /**
     * Uncompress a file.
     *
     * @param string $compressed_file
     *   The compressed file.
     *
     * @return string
     *   The uncompressed file.
     */
    private function unCompressFile(string $compressed_file): string
    {
        $extension = pathinfo($compressed_file, PATHINFO_EXTENSION);
        $new_filename = $this->getTempFile() . '.' . $extension;
        $cmd_string = "$this->convert_cmd $compressed_file -compress none $new_filename";
        $this->logger->debug('Uncompress Command:', ['cmd' => $cmd_string]);
        $result = $this->cmd->execute($cmd_string, null);
        $this->logger->debug('Uncompress Result:', ['result' => $result]);
        @unlink($compressed_file);
        return $new_filename;
    }
}
