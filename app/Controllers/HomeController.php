<?php

namespace Lockdoc\Controllers;

use Slim\Psr7\UploadedFile;
use PhpOffice\PhpWord\IOFactory;
use Lockdoc\Controllers\Controller;
use Psr\Http\Message\UploadedFileInterface;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;


Class HomeController extends Controller
{
    /**
     * 
     *  <--- GET Requests --->
     * 
     */
    public function index(Request $request, Response $response)
    {
        return $this->container->get('view')->render($response, 'home.twig');
    }

    public function viewArchive(Request $request, Response $response)
    {
        //get all files in storage directory
        $files = scandir($this->container->get('settings')['storage']);
        //remove . and .. and .DS_Store from array
        $files = array_diff($files, array('.', '..', '.DS_Store'));
        //sort files by date
        usort($files, function($a, $b) {
            return filemtime($this->container->get('settings')['storage'] . '/' . $a) < filemtime($this->container->get('settings')['storage'] . '/' . $b) ? -1 : 1;
        });
        //reverse array so newest files are at the top
        $files = array_reverse($files);
        //return view with files
        return $this->container->get('view')->render($response, 'archive.twig', [
            'files' => $files
        ]);
    }

    /**
     * 
     *  <--- POST Requests --->
     * 
     */

    /**
     *  Encrypts and stores the uploaded files
     */
    public function secure(Request $request, Response $response)
    {
        // Init Success flag
        $success = false;
        // Allowed media types
        $allowed_media_types = ['application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'];
        $media_type_map = [
            'application/pdf'       => 'pdf', 
            'application/msword'    => 'doc', 
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document'   => 'dcx',
        ];
        // Get the path to the storage folder
        $storage_path = $this->container->get('settings')['storage'];
        foreach ($_FILES as $file) {
            $fileType = reset( $file['type'] );
            //Check if uploaded file is in the allowed media types
            if ( in_array($fileType, $allowed_media_types) ) {
                $media_type = $media_type_map[$fileType];
                $tempFilePath = reset( $file['tmp_name'] );
                $encryptedFile = $this->encrypt_file($tempFilePath, $media_type);
                // Write the encrypted contents to the storage folder with the unique file name
                file_put_contents($storage_path . '/' . $encryptedFile['name'], $encryptedFile['contents'] );
                // Delete the original file
                unlink($tempFilePath);
                $success = true;
            }
        }
        // Set Flash message
        if($success){
            $this->container->get('flash')->addMessage('success', 'Files uploaded and encrypted successfully.');
        }else{
            $this->container->get('flash')->addMessage('error', 'There was an error uploading your files.');
        }

        return $this->container->get('view')->render($response, 'home.twig', [
            'flash' => $this->container->get('flash')->getMessages()
        ]);
    }

    /**
     *  Decrypt and download file
     */
    public function getFile(Request $request, Response $response)
    {
        $file           = $request->getParsedBody()['file'];
        $media_type     = substr($file, 0, 3);
        $decryptedFile  = $this->decryptFile(file_get_contents($this->container->get('settings')['storage'] . '/' . $file));
        $response->getBody()->write($decryptedFile);
        
        switch ($media_type) {
            case 'pdf':
                $response = $response->withHeader('Content-Type', 'application/pdf');
                $response = $response->withHeader('Content-Disposition', 'attachment; filename="MSK_Report.pdf"');
                break;
            case 'doc':
                $response = $response->withHeader('Content-Type', 'application/msword');
                $response = $response->withHeader('Content-Disposition', 'attachment; filename="MSK_Report.doc"');
                break;
            case 'dcx':
                $response = $response->withHeader('Content-Type', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document');
                $response = $response->withHeader('Content-Disposition', 'attachment; filename="MSK_Report.docx"');
                break;
        }

        return $response;
    }

    /**
     * 
     *  <--- Helper Methods --->
     * 
     */

    /**
     * Encrypts the contents of a file using the key-passphrase 
     *
     * @param array path to temp uploaded file.
     * @param string media type of file - doc, dcx, pdf
     * 
     * @return array encrypted file name and contents.
     */
    private function encrypt_file($file_path, $media_type) {
        $key    = $this->container->get('settings')['archive_pw'];
        $iv     = $this->container->get('settings')['iv'];

        // Read the contents of the file
        $file_contents = file_get_contents($file_path);
        
        // Encrypt the file contents using the key
        $encrypted_contents = openssl_encrypt($file_contents, 'AES-256-CBC', $key, 0, $iv);

        //Get current date and time as a string
        $now = date("d_m_Y_H_i");
        
        // Generate a unique file name for the encrypted file based on the original file name
        $encrypted_file_name = $media_type . '_' . uniqid() . '_MSK_Reports_From_' . $now . '.encrypted';

        return [
            'name'      => $encrypted_file_name,
            'contents'  => $encrypted_contents
        ];
    }

    /**
     * Decrypts the contents of a file using the key-passphrase
     *
     * @param string ecrypted file contents.
     * 
     * @return string decrypted file contents.
     */
    private function decryptFile($encrypted_contents)
    {
        $key            = $this->container->get('settings')['archive_pw'];      
        $iv             = $this->container->get('settings')['iv'];    
        $decrypted_file  = openssl_decrypt($encrypted_contents, 'AES-256-CBC', $key, 0, $iv);
        
        return $decrypted_file;
    }

}