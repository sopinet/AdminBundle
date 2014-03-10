<?php 
namespace Sopinet\Bundle\AdminBundle\Command;

use Sensio\Bundle\GeneratorBundle\Command\GeneratorCommand;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Output\Output;
use Symfony\Component\HttpKernel\KernelInterface;
use Sensio\Bundle\GeneratorBundle\Generator\BundleGenerator;
use Sensio\Bundle\GeneratorBundle\Manipulator\KernelManipulator;
use Sensio\Bundle\GeneratorBundle\Manipulator\RoutingManipulator;
use Sensio\Bundle\GeneratorBundle\Command\Helper\DialogHelper;

class AdminGeneratorCommand extends GeneratorCommand
{
    protected function configure()
    {
        $this
            ->setName('admin:generator')
            ->setDescription('Admin Generator')            
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {   
    	$dialog = $this->getDialogHelper();
    	
    	/*if ($input->isInteractive()) {
    		if (!$dialog->askConfirmation($output, $dialog->getQuestion('Do you confirm generation', 'yes', '?'), true)) {
    			$output->writeln('<error>Command aborted</error>');
    	
    			return 1;
    		}
    	}*/
    	
    	//Principalmente hariamos una comprobacion del sistema operativo y adactariamos los comandos
    	
    	//Introducimos los distintos bundles para los que queremos ejecutar el admin generator
    	$bundles = array();
    	
    	$bundles[] = "Trazeo/BaseBundle";
    	
    	echo "Generacion de entidades para el admin\n";
    	
    	foreach($bundles as $bundle){
    		
    		$path = "src/". $bundle;
    		
    		$command = "ls " . $path . "/Entity";
    		
    		$output = $this->terminal($command);
    		
    		//Limpiamos el output de los archivos de copia
    		$files = $this->deleteFilesInCopy($output);
    		
    		//Si tenemos alguna entidad creamos la carpeta admin
    		if(count($files) > 0){
    			
    			echo mkdir($path . "/Admin", 0755) 
    			?  "Carpeta Admin creada en " . $path . "\n" 
    			: "Carpeta Admin creada anteriormente\n";
    			
    			$pathToTemplateClassFile = "scripts/AdminBlank.txt";
    			$pathToTemplateServiceFile = "scripts/service.txt";
    			$pathToServiceFileInBundle = "src/" . $bundle . "/Resources/config/services.yml";
    			
    			
    			$gestorTemplateClass = fopen($pathToTemplateClassFile, 'r');
    			$gestorTemplateService = fopen($pathToTemplateServiceFile, 'r');
    			$gestorServiceFileInBundle = fopen($pathToServiceFileInBundle, 'a+');
    			
    			//Escribimos un salto de linea en los servicios
    			fwrite($gestorServiceFileInBundle, "\n");
    			
    			//Leemos la plantilla que tenemos para el class-admin
    			$templateClass = fread($gestorTemplateClass, filesize($pathToTemplateClassFile));

    			//Leemos la plantilla que tenemos para el service-admin
    			$serviceFile = fread($gestorTemplateService, filesize($pathToTemplateServiceFile));
    			
    			//Creamos una plantilla auxiliar para poder sustituir las variables para cada entidad
    			$templateClassAux = $templateClass;
    			$serviceFileAux = $serviceFile;
    			
    			//Creamos las entidades
    			foreach($files as $file){
    				
    				//Imprimimos el nombre del fichero:
    				$fileEntity = $path. "/Entity/" . $file;
    				echo "La ruta del fichero es: " . $fileEntity . "\n";
    				
    				$gestorEntity = fopen( $fileEntity,'r');
    				$classEntity = fread($gestorEntity, filesize($fileEntity));
    				
    				//echo "El fichero es: " . $classEntity;
    				
    				$vars = array();    			
    				$vars = $this->getVariables($classEntity);

    				$classAdmin = str_replace(".php", "Admin.php", $file);
    				
    				//Creamos el fichero
    				$fileAdmin = fopen($path . "/Admin/". $classAdmin ,'a');
    				
    				//Creamos la ruta para admin sin el path "src"
    				$admin = $bundle . "/Admin";
    				
    				//Creamos el contenido
    				$templateClassAux = str_replace("namespaceCADENA", str_replace("/", "\\", $admin), $templateClassAux);
    				$templateClassAux = str_replace("classCADENA", str_replace(".php", "", $classAdmin), $templateClassAux);
    				$templateClassAux = str_replace("translationDomainCADENA", str_replace("/", "", $admin), $templateClassAux);
    				$templateClassAux= str_replace("formMapperCadena", $this->getFormMapper($vars), $templateClassAux);
    				$templateClassAux= str_replace("dataGridMapperCadena", $this->getDataGridMapper($vars), $templateClassAux);
    				$templateClassAux= str_replace("listMapperCadena", $this->getListMapper($vars), $templateClassAux);
    				
    				//Insertamos la plantilla
    				fwrite($fileAdmin, $templateClassAux);
    				
    				//Limpiamos la plantilla
    				$templateClassAux = $templateClass; 
    				
    				
    				//CREACION DE SERVICIOS
    				$serviceFileAux = str_replace("TITULO", str_replace("/", ".", $admin) . "." . str_replace(".php", "", $classAdmin), $serviceFileAux);
    				$serviceFileAux = str_replace("CLASEADMIN", str_replace("/", "\\", $admin) . "\\" . str_replace(".php", "", $classAdmin), $serviceFileAux);
    				$serviceFileAux = str_replace("CENTITY", $classAdmin, $serviceFileAux);
    				$serviceFileAux = str_replace("CLASEENTITY", str_replace("/", "\\", $bundle) . "\\Entity\\" . str_replace(".php", "", $file), $serviceFileAux);
    				
    				//Incluimos al final del fichero de servicios
    				fwrite($gestorServiceFileInBundle, $serviceFileAux);
    				
    				$serviceFileAux = $serviceFile;
    				
    				
    			}
    	
    		}
    			   		
    		ld($files);
    	}
    	
    }
    
    protected function terminal($command){
    	
    	exec($command, $output);
    	
    	return $output;
    }
    
    protected function deleteFilesInCopy($files){
    	
    	//Array de archivos nuevos sin elementos basura
    	$newFiles = array();
    	
    	//Variable pos para buscar la posicion del caracter "~"
    	$pos = -1;
    	
    	foreach($files as $file){
    		
    		$pos = strstr($file, "~");
    		
    		if($pos == null)
    			$newFiles[] = $file;
    		
    	}
    	
    	return $newFiles;
    	
    }
    
    protected function getVariables($file){

    	$array = $this->getSubStringBy($file, "/**", ";", false);
    	
    	$array = $this->filterArrayBy($array, "protected");    	    	
    	
    	$vars = array();
    	
    	foreach($array as $var){    		    		
    		$vars[] = $this->getSubStringBy($var, "protected $", ";", true);    		    		    		
    	}
    	
    	return $vars;
    	    	
    	
    }
    
    protected function getSO(){
    	
    	$request = Request::createFromGlobals();
    	
    	$agent = $request->server->get('HTTP_USER_AGENT');
    	
    	echo ldd($request->server);
    }
    
    protected function createGenerator()
    {
    	return new BundleGenerator($this->getContainer()->get('filesystem'));
    }
    
    protected function getSubStringBy($file, $start, $end, $inside){
    	
    	//Array de subcadenas encontradas
    	$array = array();
    	 
    	//Cadenas para incluir la posicion de comienzo y final
    	$posStart = 0;
    	$porEnd = 0;
    	 
    	//Cadenas auxiliares para incluir la posicion de comienzo y final
    	$posStartAux = 0;
    	$porEndAux = 0;
    	 
    	//Variables para representar si hemos encontrado un inicio o un final
    	$findStart = false;
    	$findEnd = false;
    	 
    	//Variable para contar las coincidencias caracter a caracter
    	$conStart = 0;
    	$conEnd = 0;
    	 
    	for($i = 0; $i < strlen($file); $i++){
    	
    		if($file[$i] == $start[0]){ //Buscamos si no acabamos de encontrar uno
    	
    			$conStart = 0;
    			$posStartAux = $i;
    			$l = $i;
    	
    			for($j = 0; $j < strlen($start); ++$j){
    	
    				if($file[$l] == $start[$j]){
    	
    					$conStart++;
    					$l++;
    				}else{
    					break;
    				}
    					    	
    			}
    			 
    			if($conStart == strlen($start)){
    				$findStart = true;
    				
    				($inside) ? $posStart = $posStartAux + strlen($start) : $posStart = $posStartAux;
    			}
    			 
    		}
    	    	
    		if($file[$i] == $end[0] && $findStart){
    			 
    			$conEnd = 0;
    			$posEndAux = $i;
    			$l = $i;
    			 
    			for($j = 0; $j < strlen($end); ++$j){
    				 
    				if($file[$l] == $end[$j]){
    					 
    					$conEnd++;
    					$l++;
    				}else{
    					break;
    				}
    					    				
    			}
    	
    			if($conEnd == strlen($end)){
    				$findEnd = true;
    				
    				($inside) ? $posEnd = $posEndAux - strlen($end) : $posEnd = $posEndAux;
    			}
    			 
    		}
    	
    		if($findEnd && $findStart){
    	
    			$array[] = substr ( $file, $posStart, ($posEnd - $posStart) +1  );
    			$findEnd = false;
    			$findStart = false;
    			 
    		}
    	
    	}
    	
    	if(count($array) == 1)
    		return $array[0];
    	
    	return $array;
    	
    }
    
    protected function filterArrayBy($strings, $filter){
    	
    	$newArray = array();
    	
    	foreach($strings as $string){
    		
    		if(strpos($string, $filter))
    			$newArray[] = $string;
    		
    	}
    	
    	
    	return $newArray;
    	
    }
    
    protected function getFormMapper($vars){
    	
    	$formMapper = "";
    	
    	foreach($vars as $var){
    		if( strcmp($var, "id") != 0 ){
    			$formMapper = $formMapper . "->add('" . $var . "')";
    		}	
    	}
    	
    	return $formMapper;
    	
    }
    
    protected function getDataGridMapper($vars){
    	 
    	$dataGridMapper = "";
    	 
    	foreach($vars as $var){
    		if( strcmp($var, "id") != 0 ){
    			$dataGridMapper = $dataGridMapper . "->add('" . $var . "')";
    		}
    	}
    	 
    	return $dataGridMapper;
    	
    }
    
    protected function getListMapper($vars){
    	 
    	$listMapper = "";
    	 
    	foreach($vars as $var){
    		if( strcmp($var, "id") == 0 ){
    			$listMapper = $listMapper . "->addIdentifier('" . $var . "')";
    		}else{
    			$listMapper = $listMapper . "->add('" . $var . "')";
    		}
    		
    	
    	}
    	 
    	return $listMapper;
    	
    }
}

?>