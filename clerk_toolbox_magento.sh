#!/usr/bin/env bash
echo "-----------------------------------------"
echo "- Welcome To Clerk.io Magento 1 Toolbox -"
echo "-----------------------------------------"

case "$1" in
	-i|--install)
		if [ -z "$2" ]; 
		then
			echo "No extension version detectet! you have to add a second parameter with the extension version you want to install."
		else
			echo "-------------------------------------------"
			echo "- Installing Clerk.io Magento 1 Extension -"
			echo "-------------------------------------------"

			echo "-------------------"
			echo "- Creating Backup -"
			echo "-------------------"
			tar -czf clerkbackup.tar.gz . --exclude=./*.gz;
			echo "---------------------"
			echo "- Backup Completed! -"
			echo "---------------------"

			echo ""
			echo "-------------------------------"
			echo "- Downloading Clerk Extension -"
			echo "-------------------------------"
			wget "https://github.com/clerkio/clerk-magento/releases/download/${2}/ClerkForMagento-${2}.tgz"
			echo "-----------------------"
			echo "- Download Completed! -"
			echo "-----------------------"

			echo ""
			echo "------------------------------"
			echo "- Installing Clerk Extension -"
			echo "------------------------------"
			chmod +x mage
			./mage install-file "ClerkForMagento-${2}.tgz"
			echo "----------------------"
			echo "- Install Completed! -"
			echo "----------------------"

			echo ""
			echo "------------------------------------------------"
			echo "- DONE! Clerk.io Extension is now ready to use -"
			echo "------------------------------------------------"
			
		fi
		;;

	-u|--uninstall)
		echo "---------------------------------------------"
		echo "- Uninstalling Clerk.io Magento 1 Extension -"
		echo "---------------------------------------------"
		if [ "$2" == "--force" ];
		then
			rm -rf ./skin/frontend/base/default/clerk/
			rm -rf ./app/code/community/Clerk/
			rm -rf ./app/etc/modules/Clerk_Clerk.xml
			rm -rf ./app/design/frontend/base/default/layout/clerk.xml
			rm -rf ./app/design/frontend/base/default/template/clerk/
			rm -rf ./app/design/adminhtml/default/default/layout/clerk.xml
			for d in ./app/locale//*/ ; do
				rm -rf "${d}Clerk_Clerk.csv"
			done
			rm -rf ./skin/frontend/base/default/clerk/
		else
			./mage uninstall community ClerkForMagento
		fi
		echo "---------------------------"
		echo "- Uninstalling Completed! -"
		echo "---------------------------"

		echo ""
		echo "-----------------------------------------------"
		echo "- DONE! Clerk.io Extension is now Uninstalled -"
		echo "-----------------------------------------------"
		;;
	-r|--restore)
		echo "--------------------------"
		echo "- Rebuilding From Backup -"
		echo "--------------------------"
		tar -zxvf clerkbackup.tar.gz;
		echo "-------------------------"
		echo "- Rebuilding Completed! -"
		echo "-------------------------"
		;;
	-b|--backup)
		shift
			echo "-------------------"
			echo "- Creating Backup -"
			echo "-------------------"
			tar -czf clerkbackup.tar.gz . --exclude=./*.gz;
			echo "---------------------"
			echo "- Backup Completed! -"
			echo "---------------------"
		shift
		;;
	*)
		echo "-------------------"
		echo "- Toolbox Options -"
		echo "-------------------"
		echo "-i, 		--install {version}				Installing Clerk.io Magento 1 Extension"
		echo "-u, 		--uninstall 					Uninstalling Clerk.io Magento 1 Extension"
		echo "-u --force, 	--uninstall --force				Force uninstalling Clerk.io Magento 1 Extension if it have been removed wrong earlyer"
		echo "-r, 		--restore 					Restore from the backup"
		echo "-b, 		--backup 					Make full backup of Magento 1"
		exit 0
		break
		;;
esac